<?php

namespace App\Form;

use App\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyName', TextType::class, [
                'label' => 'pages.reviews.new.companyName',
            ])
            ->add('rating', IntegerType::class, [
                'label' => 'pages.reviews.new.rating',
                'attr' => [
                    'min' => 1,
                    'max' => 5,
                ],
            ])
            ->add('reviewText', TextareaType::class, [
                'label' => 'pages.reviews.new.reviewText',
            ])
            ->add('authorEmail', EmailType::class, [
                'label' => 'pages.reviews.new.authorEmail',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
            'translation_domain' => 'messages',
        ]);
    }
}
