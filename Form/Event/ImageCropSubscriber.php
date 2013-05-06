<?php

namespace Clarity\ImagesBundle\Form\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Event\DataEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Clarity\ImagesBundle\Form\Exception;
use Clarity\ImagesBundle\Form\Strategy\UploadStrategyInterface;
use Clarity\ImagesBundle\Form\Strategy\SimpleUploadStrategy;
use Symfony\Component\Form\FormError;
use Clarity\ImagesBundle\Form\Type\ImageCropType;

/**
 * @author Zmicier Aliakseyeu <z.aliakseyeu@gmail.com>
 */
class ImageCropSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var ImageCropType
     */
    protected $formType;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_BIND => 'onPreBind',
        );
    }

    /**
     * 
     */
    public function setFormType(ImageCropType $type)
    {
        $this->formType = $type;
    }

    /**
     * @param  DataEvent $event
     * @return DataEvent
     */
    public function onPreBind(DataEvent $event)
    {
        $form = $event->getForm();
        $resolver = $form->getConfig()->getType()->getOptionsResolver(); 
        $options = $resolver->resolve();

        if ($event->getData() instanceof UploadedFile) {
            $uploadStrategy = $options['upload_strategy'];
            try {
                $strategy = $this->container->get($uploadStrategy);
            } catch (ServiceNotFoundException $e) {
                $strategy = new $uploadStrategy();
            }

            if (!$strategy instanceof UploadStrategyInterface) {
                throw new Exception\UploadStrategyException(sprintf('Class "%s" must implement "%s"', $uploadStrategy, 'Clarity\ImagesBundle\Form\Strategy\UploadStrategyInterface'));
            }

            // handle situation if we use our default upload strategy: SimpleUploadStrategy
            if ($strategy instanceof SimpleUploadStrategy) {
                if (!isset($options['upload_path'])) {
                    throw new Exception\UploadStrategyException(sprintf('You are using "%s". This upload strategy requires "%s" option. For Example "%s"', 'Clarity\ImagesBundle\Form\Strategy\SimpleUploadStrategy', 'upload_path', 'web/uploads/images'));
                }
                $strategy->setUploadPath($options['upload_path']);
            }

            $uploadedFile = $strategy->upload($event->getData());

            $factory = $this->container->get('form.factory');

            $form
                ->add($factory->createNamed('uri', 'hidden', array(
                    'virtual' => true,
                )))
                ->add($factory->createNamed('x', 'hidden', array(
                    'virtual' => true,
                )))
                ->add($factory->createNamed('y', 'hidden', array(
                    'virtual' => true,
                )))
                ->add($factory->createNamed('w', 'hidden', array(
                    'virtual' => true,
                )))
                ->add($factory->createNamed('h', 'hidden', array(
                    'virtual' => true,
                )))
                ->add($factory->createNamed('sizes', 'choice', null, array(
                    'choices' => $options['crop_sizes'],
                )))
            ;
            $this->formType->setImage($uploadedFile);
            if (!$options['use_ajax']) {
                $form->addError(new FormError('clarity.form.image_crop.error.select_area'));    
            }
        } else {
            die(var_dump($event->getData()));
        }

        return $event;
    }
}