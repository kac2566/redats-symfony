<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UploadController extends AbstractController
{
    /**
     * @Route("/uploads/{filename}", name="uploads")
     */
    public function show($filename): Response
    {
        $filePath = $this->getParameter('files_directory') . '/' . $filename;
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('The file does not exist');
        }

        $response = new Response();
        $response->headers->set('Content-Type', mime_content_type($filePath));
        $response->headers->set('Content-Disposition', 'inline; filename="' . basename($filePath) . '"');
        $response->setContent(file_get_contents($filePath));

        return $response;
    }
}