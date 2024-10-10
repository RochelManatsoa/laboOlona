<?php 

namespace App\Service;

use Knp\Snappy\Pdf;

class PdfService {
    private $wkhtmltopdfPath;

    public function __construct($wkhtmltopdfPath)
    {
        $this->wkhtmltopdfPath = $wkhtmltopdfPath;
    }

    public function createPdf()
    {
        return new Pdf($this->wkhtmltopdfPath);
    }
}
