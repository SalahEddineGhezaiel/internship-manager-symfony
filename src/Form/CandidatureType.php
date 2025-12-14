<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;
use App\Entity\Candidature;

class CandidatureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lettreMotivation', TextareaType::class, [
                'label' => 'Lettre de motivation',
                'constraints' => [
                    new NotBlank(['message' => 'La lettre de motivation est requise']),
                ],
                'attr' => ['rows' => 6],
            ])
            ->add('cvFichier', FileType::class, [
                'label' => 'CV (PDF ou DOCX)',
                'mapped' => false,
                'constraints' => (function () {
                    $maxSize = '5M';
                    $notBlank = new NotBlank(['message' => 'Le CV est requis']);
                    if (\extension_loaded('fileinfo')) {
                        return [
                            $notBlank,
                            new File([
                                'maxSize' => $maxSize,
                                'mimeTypes' => [
                                    'application/pdf',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    'application/msword',
                                ],
                                'mimeTypesMessage' => 'Veuillez téléverser un fichier PDF ou DOC/DOCX valide',
                            ]),
                        ];
                    }
                    // Fallback: validate by extension when fileinfo is unavailable
                    return [
                        $notBlank,
                        new File([
                            'maxSize' => $maxSize,
                            'extensions' => ['pdf', 'doc', 'docx'],
                            'extensionsMessage' => 'Veuillez téléverser un fichier avec une extension .pdf, .doc ou .docx',
                        ]),
                    ];
                })(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Candidature::class,
        ]);
    }
}
