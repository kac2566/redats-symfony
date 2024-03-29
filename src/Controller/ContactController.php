<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ContactFormType;
use App\Form\EditContactFormType;
use Symfony\Component\HttpFoundation\RequestStack;

class ContactController extends AbstractController
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/contacts", name="contact_list")
     */
    public function index(): Response
    {
        $csvData = [];
        if (($handle = fopen('contacts.csv', 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $row = array_pad($row, 5, '');
                $csvData[] = $row;
            }
            fclose($handle);
        }

        return $this->render('contact/index.html.twig', [
            'csvData' => $csvData,
        ]);
    }

    /**
     * @Route("/contacts/add", name="add_contact")
     */
    public function create(Request $request): Response
    {
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $file = $formData['file'];
            $fileName = null;
            if ($file) {
                $fileName = md5(uniqid()) . '.' . $file->guessExtension();
                $file->move(
                    $this->getParameter('files_directory'),
                    $fileName
                );
            }

            $serverUrl = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
            $fileUrl = $fileName ? $serverUrl . $this->generateUrl('uploads', ['filename' => $fileName]) : '';

            $csvData = [
                $formData['firstName'],
                $formData['lastName'],
                $formData['email'],
                $formData['phoneNumber'] ?? '',
                $fileUrl,
            ];

            $csvFile = fopen('contacts.csv', 'a');
            fputcsv($csvFile, $csvData);
            fclose($csvFile);

            return $this->redirectToRoute('contact_list');
        }

        $formData = $form->getData();
        unset($formData['fileUrl']);

        return $this->render('contact/add.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/contacts/{id}/edit", name="edit_contact")
     */
    public function edit(Request $request, $id): Response
    {
        $csvFile = fopen('contacts.csv', 'r+');
        $contacts = [];
        while (($data = fgetcsv($csvFile)) !== false) {
            $data = array_pad($data, 5, '');
            $contacts[] = $data;
        }
        fclose($csvFile);

        $contact = $contacts[$id - 1];

        $form = $this->createForm(EditContactFormType::class, [
            'firstName' => $contact[0],
            'lastName' => $contact[1],
            'email' => $contact[2],
            'phoneNumber' => $contact[3],
            'fileUrl' => $contact[4],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $contacts[$id - 1] = [
                $formData['firstName'],
                $formData['lastName'],
                $formData['email'],
                $formData['phoneNumber'] ?? '',
                 $formData['fileUrl'] ?? '',
            ];

            $csvFile = fopen('contacts.csv', 'w');
            foreach ($contacts as $contact) {
                fputcsv($csvFile, $contact);
            }
            fclose($csvFile);

            return $this->redirectToRoute('contact_list');
        }

        return $this->render('contact/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/contacts/{id}/delete", name="delete_contact")
     */
    public function delete(Request $request, $id): Response
    {
        $csvFile = fopen('contacts.csv', 'r+');
        $contacts = [];
        while (($data = fgetcsv($csvFile)) !== false) {
            $data = array_pad($data, 5, '');
            $contacts[] = $data;
        }
        fclose($csvFile);

        if (isset($contacts[$id - 1])) {
            unset($contacts[$id - 1]);

            $csvFile = fopen('contacts.csv', 'w');
            foreach ($contacts as $contact) {
                fputcsv($csvFile, $contact);
            }
            fclose($csvFile);

            return $this->redirectToRoute('contact_list');
        } else {
            throw $this->createNotFoundException('Contact not found');
        }
    }
}