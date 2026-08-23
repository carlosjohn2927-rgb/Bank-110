<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Statements — per-account monthly PDF statement archive.
 *
 * Lists every month in which a customer has transactions and renders a
 * branded, paginated PDF on demand. Files are cached under
 * assets/statements and regenerated if missing or older than 24 hours.
 */
class Statements extends MY_Controller {

    const CACHE_TTL = 86400; // 24 hours

    public function __construct()
    {
        parent::__construct();
        $this->require_customer();
    }

    public function index()
    {
        $accounts = $this->Bank_model->accounts($this->user['id']);
        $allMonths = $this->Bank_model->statement_months($this->user['id']);

        // Build a list of statements: month → per-account availability.
        $byMonth = array();
        foreach ($allMonths as $ym) {
            $byMonth[$ym] = array('month' => $ym, 'accounts' => array());
        }
        foreach ($accounts as $a) {
            // For each account include only months where it has activity.
            $months = $this->account_months($a['id']);
            foreach ($months as $ym) {
                if (!isset($byMonth[$ym])) $byMonth[$ym] = array('month' => $ym, 'accounts' => array());
                $byMonth[$ym]['accounts'][] = $a;
            }
        }
        krsort($byMonth);

        $this->render('customer/statements', array(
            'title'    => 'Statements',
            'accounts' => $accounts,
            'months'   => $byMonth,
        ));
    }

    /** Download a single-account statement PDF. */
    public function download($account_id = NULL, $year = NULL, $month = NULL)
    {
        $account = $this->Bank_model->account((int)$account_id, $this->user['id']);
        if (!$account) show_404();

        $year = (int)$year; $month = (int)$month;
        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) show_404();

        $regenerate = $this->input->get('regen') !== NULL;
        $path = $this->cache_path($account, $year, $month);

        if (!$regenerate && is_file($path) && (time() - filemtime($path)) < self::CACHE_TTL) {
            $this->serve_pdf($path, $this->filename($account, $year, $month));
            return;
        }

        $this->build_pdf($account, $year, $month, $path);
        $this->serve_pdf($path, $this->filename($account, $year, $month));
    }

    /* -------------------- internals -------------------- */

    private function account_months($account_id)
    {
        $rows = $this->db->select("DISTINCT SUBSTR(created_at,1,7) AS ym", FALSE)
            ->where('account_id', (int)$account_id)
            ->group_by('ym')->order_by('ym', 'DESC')
            ->get('transactions')->result_array();
        return array_column($rows, 'ym');
    }

    private function cache_path($account, $year, $month)
    {
        $dir = FCPATH.'assets/statements/'.$account['user_id'];
        if (!is_dir($dir)) @mkdir($dir, 0775, TRUE);
        if (!is_file($dir.'/.htaccess')) {
            @file_put_contents($dir.'/.htaccess', "Options -Indexes\nRequire all denied\n");
            @file_put_contents($dir.'/index.html', '<!doctype html><title>403</title>');
        }
        return $dir.'/'.$year.'-'.str_pad($month,2,'0',STR_PAD_LEFT).'-'.$account['id'].'.pdf';
    }

    private function filename($account, $year, $month)
    {
        return 'NorthWest-'.preg_replace('/[^A-Za-z0-9]+/','-',$account['name']).'-'.
               $year.'-'.str_pad($month,2,'0',STR_PAD_LEFT).'.pdf';
    }

    private function build_pdf($account, $year, $month, $path)
    {
        $this->load->library('Pdf');
        $txns = $this->Bank_model->statement_transactions($account['id'], $year, $month);
        $opening = $this->Bank_model->opening_balance($account['id'], $year, $month);

        $pdf = new Pdf('NorthWest statement — '.$account['name'].' — '.date('F Y', mktime(0,0,0,$month,1,$year)));
        $pdf->set_margins(40, 42, 40, 46);
        $pdf->set_header(htmlspecialchars($account['name'].' • Statement '.date('F Y', mktime(0,0,0,$month,1,$year))));
        $user = $this->Bank_model->profile($this->user['id']);
        $pdf->set_footer(
            ($user['first_name'] ?? '').' '.($user['last_name'] ?? '').'  •  '.
            $account['account_number'].'  •  Page generated '.date('M j, Y')
        );
        $pdf->add_page();

        // Statement summary block
        $pdf->set_font('Helvetica','B',18); $pdf->set_text_color(10,31,58);
        $pdf->cell(0, 10, date('F Y', mktime(0,0,0,$month,1,$year)), 0, 1);
        $pdf->set_font('Helvetica','',9); $pdf->set_text_color(110,124,145);
        $pdf->cell(0, 5, $account['name'].'  •  '.$account['account_number'].'  •  '.$account['currency'], 0, 1);
        $pdf->ln(4);

        $debits = 0; $credits = 0;
        foreach ($txns as $t) {
            if ($t['type'] === 'credit') $credits += (float)$t['amount'];
            else $debits += (float)$t['amount'];
        }
        $closing = round($opening + $credits - $debits, 2);

        $summary = array(
            array('Opening balance', $opening),
            array('Money in (+)', $credits),
            array('Money out (−)', -$debits),
            array('Closing balance', $closing),
        );
        $colW = $pdf->page_width() / 4;
        $i = 0;
        foreach ($summary as $row) {
            $pdf->rect($pdf->get_x(), $pdf->get_y(), $colW - 8, 38, $i === 3 ? array(20,104,229) : array(244,247,251));
            $isLast = $i === 3;
            $pdf->set_font('Helvetica', '', 8.5);
            $pdf->set_text_color($isLast ? 220 : 120, $isLast ? 230 : 130, $isLast ? 255 : 150);
            $pdf->text_at($pdf->get_x() + 10, $pdf->get_y() + 10, $row[0]);
            $pdf->set_font('Helvetica', 'B', 15);
            $pdf->set_text_color($isLast ? 255 : 15, 45, 80);
            $pdf->text_at($pdf->get_x() + 10, $pdf->get_y() + 25, $this->money($row[1], $account['currency']));
            $pdf->set_x($pdf->get_x() + $colW);
            $i++;
        }
        $pdf->set_x($pdf->marginL()); $pdf->set_y($pdf->get_y() + 46);
        $pdf->set_text_color(0,0,0);

        // Transactions table header
        $pdf->ln(2);
        $w  = $pdf->page_width();
        $cols = array(
            'date'        => 70,
            'reference'   => 95,
            'description' => 0, // flexible
            'debit'       => 70,
            'credit'      => 70,
            'balance'     => 78,
        );
        $descW = $w - array_sum($cols) + $cols['description'];

        $pdf->set_font('Helvetica','B',8.5); $pdf->set_text_color(110,124,145);
        $pdf->cell($cols['date'], 8, 'DATE', 'B', 0);
        $pdf->cell($cols['reference'], 8, 'REFERENCE', 'B', 0);
        $pdf->cell($descW, 8, 'DESCRIPTION', 'B', 0);
        $pdf->cell($cols['debit'], 8, 'MONEY OUT', 'B', 0, 'R');
        $pdf->cell($cols['credit'], 8, 'MONEY IN', 'B', 0, 'R');
        $pdf->cell($cols['balance'], 8, 'BALANCE', 'B', 1, 'R');
        $pdf->set_text_color(0,0,0);

        if (empty($txns)) {
            $pdf->set_font('Helvetica','',10); $pdf->set_text_color(120,130,145);
            $pdf->cell(0, 22, 'No transactions for this period.', 0, 1);
            $pdf->set_text_color(0,0,0);
        } else {
            $running = $opening;
            $pdf->set_font('Helvetica','',9);
            foreach ($txns as $t) {
                $amt = (float)$t['amount'];
                if ($t['type'] === 'credit') { $running += $amt; $debit=''; $credit=$this->money($amt,$t['currency']); }
                else { $running -= $amt; $debit=$this->money($amt,$t['currency']); $credit=''; }
                $y0 = $pdf->get_y();
                $rowH = 13;
                // Alternating row background
                static $alt = FALSE; $alt = !$alt;
                if ($alt) $pdf->rect($pdf->marginL(), $y0, $w, $rowH, array(249,251,254));

                $pdf->cell($cols['date'], $rowH, date('M j, Y', strtotime($t['transaction_date'])), 0, 0);
                $pdf->cell($cols['reference'], $rowH, strtoupper($t['reference']), 0, 0);
                // Description may wrap; use simple truncation to keep rows aligned.
                $desc = $t['description'];
                if ($pdf->text_width($desc) > $descW - 6) {
                    while ($pdf->text_width($desc.'…') > $descW - 6 && strlen($desc) > 4) $desc = substr($desc,0,-1);
                    $desc .= '…';
                }
                $pdf->cell($descW, $rowH, $desc, 0, 0);
                $pdf->set_text_color(198,59,89);
                $pdf->cell($cols['debit'], $rowH, $debit, 0, 0, 'R');
                $pdf->set_text_color(21,140,90);
                $pdf->cell($cols['credit'], $rowH, $credit, 0, 0, 'R');
                $pdf->set_text_color(0,0,0);
                $pdf->set_font('Helvetica','B',9);
                $pdf->cell($cols['balance'], $rowH, $this->money($running, $t['currency']), 0, 1, 'R');
                $pdf->set_font('Helvetica','',9);
            }
        }

        $pdf->output($this->filename($account, $year, $month), 'F', $path);
    }

    private function money($amount, $currency = 'USD')
    {
        $sym = array('USD'=>'$','EUR'=>'€','GBP'=>'£');
        $s = isset($sym[$currency]) ? $sym[$currency] : $currency.' ';
        return $s.number_format((float)$amount, 2);
    }

    private function serve_pdf($path, $filename)
    {
        if (!is_file($path)) show_404();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="'.$filename.'"');
        header('Content-Length: '.filesize($path));
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;
    }
}
