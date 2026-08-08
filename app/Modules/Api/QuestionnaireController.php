<?php

declare(strict_types=1);

namespace App\Modules\Api;

use App\Core\Request;
use App\Support\Mailer;

final class QuestionnaireController extends ApiController
{
    private const RECIPIENT = 'fshehadeh@gmail.com';

    public function submit(Request $request): never
    {
        // Reject non-POST or empty submissions
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('Method not allowed.', 405);
        }

        $summary = trim((string) $request->input('formatted_summary', ''));
        $subject = trim((string) $request->input('_subject', 'Byblos HR Setup Questionnaire'));

        if ($subject === '') {
            $subject = 'Byblos HR Setup Questionnaire';
        }

        if ($summary === '') {
            $this->error('Empty submission — no summary field received.');
        }

        $html = $this->buildHtml($subject, $summary);

        try {
            $mailer = new Mailer((array) config('app.mail', []));
            $mailer->send(self::RECIPIENT, $subject, $html, $summary);
            $this->json(['ok' => true]);
        } catch (\Throwable $e) {
            error_log('[QuestionnaireController] Mail failed: ' . $e->getMessage());
            // Return ok=true anyway — the client already saw the summary on screen
            $this->json(['ok' => true, 'mail_error' => $e->getMessage()]);
        }
    }

    private function buildHtml(string $subject, string $summary): string
    {
        $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $safeBody    = nl2br(htmlspecialchars($summary, ENT_QUOTES, 'UTF-8'));
        $date        = date('d M Y, H:i');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="utf-8"><title>{$safeSubject}</title></head>
        <body style="font-family:Arial,sans-serif;font-size:14px;color:#222;max-width:700px;margin:0 auto;padding:24px">
          <div style="background:#5B8DB8;color:#fff;padding:20px 24px;border-radius:6px 6px 0 0">
            <h2 style="margin:0;font-size:18px">Byblos Printing SAL — HR Setup Questionnaire</h2>
            <p style="margin:4px 0 0;font-size:12px;opacity:.85">Submitted {$date}</p>
          </div>
          <div style="background:#f5f8fb;border:1px solid #dde6ee;border-top:none;padding:24px;border-radius:0 0 6px 6px;white-space:pre-wrap;font-family:monospace;font-size:13px;line-height:1.7">
            {$safeBody}
          </div>
        </body>
        </html>
        HTML;
    }
}
