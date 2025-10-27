<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l\'article',
                'attr' => [
                    'placeholder' => 'Saisissez le titre de votre article',
                    'class' => 'form-control'
                ]
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'nom',
                'required' => false,
                'placeholder' => 'Sélectionnez une catégorie',
                'label' => 'Catégorie existante',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('nouvelle_categorie', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Ou créer une nouvelle catégorie',
                'attr' => [
                    'placeholder' => 'Nom de la nouvelle catégorie (optionnel)',
                    'class' => 'form-control'
                ]
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu de l\'article',
                'attr' => [
                    'rows' => 15,
                    'class' => 'form-control',
                    'placeholder' => 'Rédigez votre article ici...'
                ]
            ])
            ->add('image', FileType::class, [
                'label' => 'Image de couverture',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG ou WEBP)',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('statut', HiddenType::class, [
                'data' => 'draft'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}