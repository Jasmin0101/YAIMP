<?php

namespace App\Form;

use App\Entity\Application;
use App\Enums\ActionEnum;
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
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'required' => true,
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'required' => true,
            ])
            ->add('portfolio_id', IntegerType::class, [
                'label' => 'Portfolio ID',
                'required' => true,
            ])
            ->add('stock_id', IntegerType::class, [
                'label' => 'Stock ID',
                'required' => true,
            ])
            ->add('action', EnumType::class, [
                'class' => ActionEnum::class,
                'choice_label' => function ($action) {
                    return $action->getLabel(); // Assuming you have a method to get the label
                },
                'label' => 'Action',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Application::class,
        ]);
    }
}
