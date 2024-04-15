<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AccessCodeFormType extends AbstractType
{
    public const FIELD_CODE = 'access_code';
    public const FIELD_NAME = 'name';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(self::FIELD_CODE, TextType::class)
            ->add(self::FIELD_NAME, TextType::class)
        ;
    }
}
