<?php

namespace Admin\QuestionBundle\Controller;

use BaseBundle\Entity\Choice;
use BaseBundle\Entity\Enumerations\Topic;
use BaseBundle\Entity\Question;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="admin_questions")
     */
    public function indexAction()
    {
        $questions = $this->getDoctrine()->getRepository(Question::class)->findAll();
        $topics = Topic::getEnumAsArray();
        foreach ($questions as $question){
            $choices = $this->getDoctrine()->getRepository(Choice::class)->findBy(array('question' => $question));
            foreach ($choices as $choice){
                $question->addChoice($choice);
            }
        }
        return $this->render('AdminQuestionBundle:Default:index.html.twig', array(
            'questions' => $questions,
            'topics' => $topics
        ));
    }

    /**
     * @Route("/delete", name="admin_question_delete")
     */
    public function deleteQuestionAction(Request $request){
        if($request->isXmlHttpRequest()){
            $question = $this->getDoctrine()->getRepository(Question::class)->find($request->get('id'));
            $this->getDoctrine()->getManager()->remove($question);
            $this->getDoctrine()->getManager()->flush();
            return new Response(Response::HTTP_OK);
        }
        return new Response(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @Route("/all", name="admin_get_questions")
     */
    public function getQuestionsAction(Request $request){
        if($request->isXmlHttpRequest()){
            $normalizer = new ObjectNormalizer();
            $normalizer->setCircularReferenceLimit(1);
            // Add Circular reference handler
            $normalizer->setCircularReferenceHandler(function ($object) {
                return $object->getId();
            });
            $serializer = new Serializer(array($normalizer));

            $questions = $this->getDoctrine()->getRepository(Question::class)->findAll();
            foreach ($questions as $question){
                $choices = $this->getDoctrine()->getRepository(Choice::class)->findBy(array('question' => $question));
                foreach ($choices as $choice){
                    $question->addChoice($choice);
                }
            }
            $data = $serializer->normalize($questions);
            return new JsonResponse($data);
        }
        return new Response(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @Route("/add", name="admin_add_question")
     */
    public function addQuestionAction(Request $request){
        if($request->isXmlHttpRequest()){
            $dom = $request->get('question');
            $question = new Question();
            $question->setQuestion($dom['question']);
            $question->setTopic($dom['topic']);
            $this->getDoctrine()->getManager()->persist($question);
            $this->getDoctrine()->getManager()->flush();

            $choices = $dom['choices'];
            foreach ($choices as $choiceString){
                $choice = new Choice();
                $choice->setQuestion($question);
                $choice->setChoice($choiceString);
                $this->getDoctrine()->getManager()->persist($choice);
                $this->getDoctrine()->getManager()->flush();
            }
            return new Response(Response::HTTP_OK);
        }
        return new Response(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @Route("/update", name="admin_update_question")
     */
    public function updateQuestionAction(Request $request){
        if($request->isXmlHttpRequest()){
            $dom = $request->get('question');
            $question = $this->getDoctrine()->getRepository(Question::class)->find($dom['question']['id']);
            $question->setQuestion($dom['question']['question']);
            $question->setTopic($dom['topic']);
            $this->getDoctrine()->getManager()->persist($question);
            $this->getDoctrine()->getManager()->flush();

            $toRemoveChoices = $this->getDoctrine()->getRepository(Choice::class)->findBy(array('question' => $question));
            foreach ($toRemoveChoices as $toRemoveChoice){
                $this->getDoctrine()->getManager()->remove($toRemoveChoice);
            }

            $choices = $dom['choices'];
            foreach ($choices as $choiceString){
                $choice = new Choice();
                $choice->setChoice($choiceString);
                $choice->setQuestion($question);
                $this->getDoctrine()->getManager()->persist($choice);
                $this->getDoctrine()->getManager()->flush();
            }
            return new Response(Response::HTTP_OK);
        }
        return new Response(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
