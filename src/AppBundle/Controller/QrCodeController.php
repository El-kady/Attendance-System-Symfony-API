<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Endroid\QrCode\Factory\QrCodeFactory;
use Symfony\Component\HttpFoundation\Response;

class QrCodeController extends Controller
{
    /**
     * @Route("/qrcode/{branch}/{code}.{extension}", name="endroid_qrcode", requirements={"text"="[\w\W]+", "extension"="jpg|png|gif"})
     */
    public function generateAction(Request $request,$branch, $code, $extension)
    {

        $db = $this->getDoctrine()->getManager();
        $branch = $db->getRepository('AppBundle:Branch')->find((int) $branch);

        if ($branch) {
            $branch->setQrcode($code);
            $db->flush();
        }

        $options = $request->query->all();

        $qrCode = $this->getQrCodeFactory()->createQrCode($options);
        $qrCode->setText($code);

        $mime_type = 'image/'.$extension;
        if ($extension == 'jpg') {
            $mime_type = 'image/jpeg';
        }

        return new Response($qrCode->get($extension), 200, ['Content-Type' => $mime_type]);
    }

    /**
     * Returns the QR code factory.
     *
     * @return QrCodeFactory
     */
    protected function getQrCodeFactory()
    {
        return $this->get('endroid.qrcode.factory');
    }
}
