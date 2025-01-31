<?php

namespace App\Form;
use App\Entity\Application;
use App\Enums\ActionEnum;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('price', NumberType::class)
            ->add('quantity', IntegerType::class, [
                'required' => true,
                'data' => '0', // Подставляет 0, если ничего не передано
            ])
            ->add('portfolio', IntegerType::class)
            ->add('stock', IntegerType::class)
            ->add(
                'action',
                EnumType::class,
                ['class' => ActionEnum::class,]
            );
    }

    public function configurationOptions(OptionsResolver $resliver)
    {
        $resliver->setDefaults([

            'data_class' => Application::class

        ]);
    }
}