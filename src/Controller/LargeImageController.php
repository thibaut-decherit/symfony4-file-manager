<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LargeImageController
 * @package App\Controller
 *
 * @Route("/large-image")
 */
class LargeImageController extends DefaultController
{
    /**
     * Renders homepage.
     *
     * @Route("/upload", name="large_image_upload_view", methods="GET")
     * @return Response
     */
    public function uploadView()
    {
        return $this->render('form/image_file/upload.html.twig');
    }
}
