<?php

namespace App\Controller;

use App\Entity\FileChunk;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    /**
     * Renders homepage.
     *
     * @param Request $request
     * @Route("/upload-chunk-ajax", name="large_image_upload_chunk_ajax", methods="POST")
     * @return JsonResponse
     */
    public function uploadChunk(Request $request)
    {
        $chunkNumber = $request->get('id');
        $metadata = (array)json_decode($request->get('metadata'));
        $fileChunk = new FileChunk($chunkNumber, $metadata, $request->files->get('file'));
        dump($fileChunk);
        return new JsonResponse();
    }
}
