<?php

namespace App\Form\Admin;

use App\Entity\ConnectedDevice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConnectedDeviceFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ip', TextType::class)
            ->add('userAgent', TextType::class)
            ->add('hash', TextType::class, ['disabled' => true])
            ->add('lastAccessed', DateTimeType::class, ['disabled' => true])
            ->add('active', CheckboxType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConnectedDevice::class,
        ]);
    }
}
