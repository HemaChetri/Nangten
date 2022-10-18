<?php
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\RendererInterface;


class PrintController extends AbstractActionController
{

    /**
     * @var \TCPDF
     */
    protected $tcpdf;

    /**
     * @var RendererInterface
     */
    protected $renderer;

    public function __construct($tcpdf, $renderer)
    {
        $this->tcpdf = $tcpdf;
        $this->renderer = $renderer;
    }

    public function indexAction()
    {
		if($this->getRequest()->isPost()):
			$data = $this->getRequest()->getPost();
		endif;

        $html= '<style>td{border:1px solid #ddd}tr.first{text-align:center}.table-primary{background-color:#d2e1f3;color:#1e293b;border-color:#c0cfe1}.table-success{background-color:#d5f0da;color:#1e293b;border-color:#c3dcca}.table-info{background-color:#d9ebf9;color:#1e293b;border-color:#c6d8e6}.table-warning{background-color:#fde1cd;color:#1e293b;border-color:#e7cfbe}.table-danger{background-color:#f7d7d7;color:#1e293b;border-color:#e1c6c7}.text-start{text-align:left!important}.text-end{text-align:right!important}.text-center{text-align:center!important}.fs-1{font-size:24px!important}.fs-2{font-size:20px!important}.fs-3{font-size:16px!important}.fs-4{font-size:14px!important}.fs-5{font-size:12px!important}.fs-6{font-size:10px!important}.fw-bold{font-weight:700}.text-wrap{white-space:normal!important}</style>
            '.$data['dom'];

        $pdf = $this->tcpdf;
        $pdf->SetTitle($data['title']);

        $pdf->SetFont('times', '', 11, '', false);
        
        $pdf->AddPage($data['orentation'], $data['size']);
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output();
    }
}
