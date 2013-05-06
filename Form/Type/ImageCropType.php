<?php

namespace Clarity\ImagesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Zmicier Aliakseyeu <z.aliakseyeu@gmail.com>
 */
class ImageCropType extends AbstractType
{
    /**
     * @var \Symfony\Component\EventDispatcher\EventSubscriberInterface
     */
    protected $subscriber;

    /**
     * @param \Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber
     */
    public function __construct(EventSubscriberInterface $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->subscriber);
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'crop_strategy' => null,
            'height' => 0,
            'width' => 0,
            'compound' => true,
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'image_upload';
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'image_crop';
    }
}