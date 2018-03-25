<?php

namespace NewsFeedBundle\Controller;

use BaseBundle\Entity\Enumerations\PostType;
use BaseBundle\Entity\Enumerations\ReactionType;
use BaseBundle\Entity\Photo;
use BaseBundle\Entity\Post;
use BaseBundle\Entity\PostReaction;
use BaseBundle\Repository\PostReactionRepository;
use BaseBundle\Repository\PhotoRepository;
use NewsFeedBundle\Service\PostService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class NewsFeedController extends Controller
{
    /**
     * @Route("/", name="news_feed")
     */
    public function newsFeedAction()
    {
        /**@var PhotoRepository $repo */

        $user = $this->container->get("security.token_storage")->getToken()->getUser();
        $posts = PostService::getPosts($this->getDoctrine(), $user);
        $repo = $this->getDoctrine()->getRepository(Photo::class);
        $photoUrl = $repo->getProfilePhotoUrl($user);

        return $this->render('NewsFeedBundle:NewsFeed:news_feed.html.twig', array(
            'posts' => $posts,
            'photo' => $photoUrl,
            'online' => $user,
            'StatusType' => PostType::Status,
            'PictureType' => PostType::Picture
        ));
    }

    /**
     * @Route("create_post", name = "create_post")
     * @param Request $request
     * @return JsonResponse
     */
    public function createPostAction(Request $request){
        if($request->isXmlHttpRequest()){
            $text = $request->get("text");
            $user = $this->container->get("security.token_storage")->getToken()->getUser();
            $post = new Post;
            $post->setContent($text);
            $post->setUser($user);
            $post->setDate(new \DateTime());

            $em = $this->getDoctrine()->getManager();
            $em->persist($post);
            $em->flush();

            $serializer = new Serializer([new ObjectNormalizer()]);
            $data = $serializer->normalize(['id' => $post->getId()]);

            return new JsonResponse($data);
        }
    }

    /**
     * @Route("edit_post", name = "edit_post")
     * @param Request $request
     * @return JsonResponse
     */
    public function editPostAction(Request $request){
        if($request->isXmlHttpRequest()){
            $text = $request->get('text');
            $id = $request->get('id');

            $post = $this->getDoctrine()->getRepository(Post::class)->find($id);
            $post->setContent($text);

            $em = $this->getDoctrine()->getManager();
            $em->persist($post);
            $em->flush();

            return new JsonResponse();
        }
    }

    /**
     * @Route("delete_post", name = "delete_post")
     * @param Request $request
     * @return JsonResponse
     */
    public function deletePostAction(Request $request){
        if($request->isXmlHttpRequest()){
            /** @var PostReactionRepository $reactRepo */

            $id = $request->get('id');
            $post = $this->getDoctrine()->getRepository(Post::class)->find($id);
            $em = $this->getDoctrine()->getManager();
            $em->remove($post);
            $em->flush();
            $reactRepo = $this->getDoctrine()->getRepository(PostReaction::class);
            $reactRepo->deleteReactionBy($id);
            return new JsonResponse();
        }
    }

    /**
     * @Route("react", name = "react")
     * @param Request $request
     * @return JsonResponse
     */
    public function reactAction(Request $request){
        /** @var PostReaction $reaction */
        if($request->isXmlHttpRequest()){
            /* Parse data */
            $data = [];
            $user = $this->container->get("security.token_storage")->getToken()->getUser();
            $id = $request->get('id');
            $type = $request->get('type');
            $reactionType = $request->get('reaction');
            $reactionType = ReactionType::getEnumAsArray()[$reactionType];
            $postId = 0;
            $photoId = 0;
            if($type == PostType::Status)
                $postId = $id;
            else if($type == PostType::Picture)
                $photoId = $id;

            /* Check if reaction exists already. */
            $exists = $this->getDoctrine()->getRepository(PostReaction::class)->findBy([
                'postId' => $postId,
                'user' => $user
            ]);
            $exists = array_merge($exists, $this->getDoctrine()->getRepository(PostReaction::class)->findBy([
                'photoId' => $photoId,
                'user' => $user
            ]));

            /* If reaction exists */
            if(!empty($exists)){
                $reaction = $exists[0];

                /* If same reaction */
                if($reaction->getReaction() == $reactionType){
                    $this->deleteReaction($reaction);
                    $data = [
                        'title' => 'None'
                    ];
                }
                /* If different reaction */
                else{
                    $reaction->setReaction($reactionType);
                    $this->updateReaction($reaction);
                    $data = [
                        'title' => ReactionType::getName($reactionType)
                    ];
                }
            }
            /* If reaction doesn't exist */
            else{
                $data = [
                    'title' => ReactionType::getName($reactionType)
                ];
                $reaction = new PostReaction();
                $reaction->setUser($user);
                $reaction->setExperienceId(0);
                $reaction->setReaction($reactionType);
                $reaction->setPostId($postId);
                $reaction->setPhotoId($photoId);

                $this->updateReaction($reaction);
            }

            $serializer = new Serializer([new ObjectNormalizer()]);
            $data = $serializer->normalize($data);
            return new JsonResponse($data);
        }
    }

    function deleteReaction($reaction){
        $em = $this->getDoctrine()->getManager();
        $em->remove($reaction);
        $em->flush();
    }

    function updateReaction($reaction){
        $em = $this->getDoctrine()->getManager();
        $em->persist($reaction);
        $em->flush();
    }
}
