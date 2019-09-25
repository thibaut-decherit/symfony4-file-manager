<?php

namespace App\Controller;

use App\Entity\LargeImage;
use App\Service\FileChunkUploaderService\FileChunkUploaderService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LargeImageController
 * @package App\Controller
 *
 * @Route("/large-image")
 * @IsGranted("ROLE_USER")
 */
class LargeImageController extends DefaultController
{
    /**
     * Renders upload view.
     *
     * @Route("/upload", name="large_image_upload_view", methods="GET")
     * @return Response
     */
    public function uploadView()
    {
        return $this->render('form/image_file/upload.html.twig');
    }

    /**
     * Handles file chunk upload POST request.
     *
     * @param Request $request
     * @param FileChunkUploaderService $fileChunkUploaderService
     * @Route("/upload-chunk-ajax", name="large_image_upload_chunk_ajax", methods="POST")
     * @return JsonResponse
     */
    public function uploadChunk(Request $request, FileChunkUploaderService $fileChunkUploaderService)
    {
        $path = $fileChunkUploaderService->handleUpload(
            $request,
            LargeImage::class,
            $this->getUser()
        );

//        if(is_string($path)) {
//            $em = $this->getDoctrine()->getManager();
//            $largeImage = new LargeImage();
//
//            $em->persist($largeImage);
//            $em->flush();
//        }

        return new JsonResponse();
    }
}
