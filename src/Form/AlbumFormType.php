<?php

namespace App\Form;

use App\Entity\Albums;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AlbumFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, 
            [
                'label' => ' ',
                'attr' => array('class'=>'input is-rounded mt-2',
                'type'=>'text', 'placeholder' => 'Название')
            ])
            ->add('submit', SubmitType::class,
            ['label' => 'Создать',
            'attr' => array('class'=>'button is-small is-rounded is-primary is-outlined mt-2 mb-2')])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Albums::class,
        ]);
    }
}
