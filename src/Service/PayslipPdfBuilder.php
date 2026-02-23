<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Driver;
use App\Entity\User;
use Twig\Environment;

final class PayslipPdfBuilder
{
    public function __construct(private readonly Environment $twig) {}

    public function build(User $user, Driver $driver, array $data, \DateTimeImmutable $start, \DateTimeImmutable $end): string
    {
        $fullName = trim(((string) $user->getFirstName()) . ' ' . ((string) $user->getLastName()));
        if ($fullName === '') {
            $fullName = (string) $user->getEmail();
        }
        $html = $this->twig->render('admin/payslip_pdf.html.twig', [
            'user' => $user,
            'driver' => $driver,
            'fullName' => $fullName,
            'neooStart' => $start,
            'neooEnd' => $end,
            'data' => $data,
            'logoDataUri' => $this->logoDataUri(),
        ]);
        return $this->wkhtmltopdf($html);
    }

    private function wkhtmltopdf(string $html): string
    {
        $tmpDir = sys_get_temp_dir();
        $input = tempnam($tmpDir, 'payslip_html_');
        $output = tempnam($tmpDir, 'payslip_pdf_');
        if ($input === false || $output === false) {
            throw new \RuntimeException('Unable to create temp files');
        }
        $inputFile = $input . '.html';
        $outputFile = $output . '.pdf';
        rename($input, $inputFile);
        rename($output, $outputFile);
        file_put_contents($inputFile, $html);
        $bin = $this->wkhtmltopdfPath();
        $cmd = escapeshellarg($bin) . ' --encoding utf-8 --page-size A4 --margin-top 12 --margin-right 12 --margin-bottom 12 --margin-left 12 ' . escapeshellarg($inputFile) . ' ' . escapeshellarg($outputFile);
        $outputLines = [];
        $status = 0;
        exec($cmd, $outputLines, $status);
        if ($status !== 0 || !is_file($outputFile)) {
            $message = implode("\n", $outputLines);
            $this->cleanup([$inputFile, $outputFile]);
            throw new \RuntimeException($message !== '' ? $message : 'wkhtmltopdf failed');
        }
        $pdf = file_get_contents($outputFile);
        $this->cleanup([$inputFile, $outputFile]);
        return $pdf === false ? '' : $pdf;
    }

    private function wkhtmltopdfPath(): string
    {
        $envPath = $_SERVER['WKHTMLTOPDF_PATH'] ?? $_ENV['WKHTMLTOPDF_PATH'] ?? '';
        return $envPath !== '' ? $envPath : 'wkhtmltopdf';
    }

    private function cleanup(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function money(float $value): string
    {
        return number_format($value, 3, '.', ' ') . ' EUR';
    }

    private function percent(float $value): string
    {
        return number_format($value, 3, '.', ' ') . ' %';
    }

    private function number(float $value): string
    {
        return number_format($value, 3, '.', ' ');
    }

    private function logoDataUri(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="220" height="60" viewBox="0 0 220 60"><rect width="220" height="60" rx="10" fill="#1f4ed8"/><text x="110" y="38" text-anchor="middle" font-size="26" font-family="Arial, sans-serif" fill="#ffffff">NEOO DRIVER</text></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
