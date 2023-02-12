<?php

namespace App\Controller;

use App\Entity\Albums;
use App\Entity\Photos;
use App\Form\AlbumFormType;
use App\Form\PhotoFormType;
use App\Repository\AlbumsRepository;
use App\Repository\PhotosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GalleryController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    /**
     * @Route("/", name="homepage")
     */
    public function index(Request $request, ManagerRegistry $managerRegistry, AlbumsRepository $albumsRepository): Response
    {
        $album = new Albums();
        $form = $this->createForm(AlbumFormType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $name_album = $form['name']->getData();

            $album
                ->setOwner($this->getUser())
                ->setName($name_album);
                
            $this->entityManager->persist($album);
            $this->entityManager->flush();

            return $this->redirectToRoute('homepage');
        }

        if ($this->isGranted('IS_AUTHENTICATED')){
            $user_id = $this->getUser()->getId();
            $user_albums = $albumsRepository->findUserAlbums($user_id);
            $all_albums =  $albumsRepository->findAllExceptOwner($this->getUser()->getId());
        }
        else{
            $user_albums = [];
            $all_albums = $albumsRepository->findAllWithSize(); 
        }

        return $this->render('gallery/index.html.twig', [
            'controller_name' => 'GalleryController',
            'user_albums' => $user_albums,
            'all_albums' => $all_albums,
            'album_form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/album/{id}", name="album")
     */
    public function show_album(Request $request, PhotosRepository $photosRepository, AlbumsRepository $albumsRepository, 
    int $id, string $photoDir): Response
    {
        $album = $albumsRepository->find($id);

        if (!$album){
            throw $this->createNotFoundException('Альбома с таким id не найдено');
        }

        $photos = $photosRepository->findBy(['album' => $id]);
        $photo = new Photos();
        $form = $this->createForm(PhotoFormType::class, $photo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $photo->setAlbum($albumsRepository->find($id));
            $photo_from_form = $form['path']->getData();
            $filename = bin2hex(random_bytes(6)).'.'.$photo_from_form->guessExtension();
            try {
                $photo_from_form->move($photoDir, $filename);
                } catch (FileException $e) {
                    // фото не загрузилось
                }
                $photo->setPath($filename);
                
            $this->entityManager->persist($photo);
            $this->entityManager->flush();

            return $this->redirectToRoute('album', ['id' => $id]);
        }

        if ($this->isGranted('IS_AUTHENTICATED')){
            $is_owner = $albumsRepository->find($id)->getOwner() == $this->getUser()->getUserIdentifier();
        }else{
            $is_owner = false;
        }

        return $this->render('gallery/album.html.twig', [
            'controller_name' => 'GalleryController',
            'photos' => $photos,
            'photo_form' => $form->createView(),
            'is_owner' => $is_owner,
            'album' => $album,
         ]);
    }

    /**
     * @Route("/edit-album/{id}", name="edit_album")
    */
    public function edit_album(Request $request, PhotosRepository $photosRepository, AlbumsRepository $albumsRepository, int $id): Response
    {
        $album = $albumsRepository->find($id);

        if (!$album){
            throw $this->createNotFoundException('Альбома с таким id не найдено');
        }

        if ($album && !($this->isGranted('IS_AUTHENTICATED') &&
        $albumsRepository->find($id)->getOwner() == $this->getUser()->getUserIdentifier())){
            return $this->redirectToRoute('homepage');
        }

        $photos = $photosRepository->findBy(['album' => $id]);
        $albums = $albumsRepository->findAlbumsExceptCurrent($this->getUser()->getId(), $id);
        

        if ($request->getMethod() == "POST"){
            $new_name = $request->get("new_name");
            $album->setName($new_name);
            $this->entityManager->flush();
        }

        return $this->render('gallery/album_edit.html.twig', [
            'controller_name' => 'GalleryController',
            'photos' => $photos,
            'albums' => $albums,
            'current_album' => $album,
         ]);
    }


    /**
     * @Route("/delete-album", name="delete_album")
     */
    public function delete_album(Request $request, AlbumsRepository $albumsRepository, PhotosRepository $photosRepository, string $photoDir){
        $album_id = $request->get("delete_album");
        $album = $albumsRepository->find($album_id);

        $photos = $photosRepository->findBy(['album' => $album_id]);

        $this->entityManager->remove($album);
        $this->entityManager->flush();
     
        $filesystem = new Filesystem();

        foreach ($photos as $photo) {
            $photo_path = $photoDir."/".$photo->getPath();
            if ($filesystem->exists($photo_path)){
                $filesystem->remove($photo_path);
            }
        }

        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/delete-photo", name="delete_photo")
     */
    public function delete_photo(Request $request, PhotosRepository $photosRepository, string $photoDir){
        $photo_id = $request->get("delete_photo");
        
        $filesystem = new Filesystem();
        
        $photo = $photosRepository->find($photo_id);

        $this->entityManager->remove($photo);
        $this->entityManager->flush();
     
        $photo_path = $photoDir."/".$photo->getPath();
        
        if ($filesystem->exists($photo_path)){
            $filesystem->remove($photo_path);
        }

        return $this->redirectToRoute('edit_album', ['id' => $photo->getAlbum()->getId()]);
    }

    /**
     * @Route("/edit-photo", name="edit_photo")
     */
    public function edit_photo(Request $request, PhotosRepository $photosRepository, AlbumsRepository $albumsRepository){
        $photo_id = $request->get("edit_photo");
        $new_album = $request->get("select_album");
        
        $photo = $photosRepository->find($photo_id);
        $old_album = $photo->getAlbum()->getId();
        $photo->setAlbum($albumsRepository->find($new_album));

        $this->entityManager->flush();
     
        return $this->redirectToRoute('edit_album', ['id' => $old_album]);
    }


}
