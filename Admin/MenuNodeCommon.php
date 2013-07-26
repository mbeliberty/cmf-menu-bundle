<?php

namespace Symfony\Cmf\Bundle\MenuBundle\Admin;

use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrinePHPCRAdminBundle\Admin\Admin;
use Symfony\Cmf\Bundle\MenuBundle\Model\MenuNode;
use Symfony\Component\HttpFoundation\Request;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Symfony\Cmf\Bundle\MenuBundle\ContentAwareFactory;
use Doctrine\Common\Util\ClassUtils;

class MenuNodeCommon extends Admin
{
    protected $contentAwareFactory;
    protected $locales;
    protected $translationDomain = 'CmfMenuBundle';

    /**
     * @param string $code
     * @param string $class
     * @param string $baseControllerName
     * @param array  $locales
     */
    public function __construct($code, $class, $baseControllerName, $locales)
    {
        parent::__construct($code, $class, $baseControllerName);

        $this->locales = $locales;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id', 'text')
            ->add('name', 'text')
            ->add('label', 'text')
            ->add('uri', 'text')
            ->add('route', 'text')
            ;

        $listMapper
            ->add('locales', 'choice', array(
                'template' => 'SonataDoctrinePHPCRAdminBundle:CRUD:locales.html.twig'
            ))
        ;
    }

    protected function isSubjectNotNew()
    {
        return $this->hasSubject() && null !== $this->getSubject()->getId();
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('form.group_general')
                ->add(
                    'parent',
                    'doctrine_phpcr_odm_tree',
                    array('root_node' => $this->menuRoot, 'choice_list' => array(), 'select_root_node' => true)
                )
                ->add('name', 'text',
                    $this->isSubjectNotNew() ? array(
                        'attr' => array('readonly' => 'readonly')
                    ) : array()
                )
                ->add('label', 'text')
            ->end()
        ;

        // what does this line do? @elHorner?
        if (null === $this->getParentFieldDescription()) {

            // Add the choice for the node links "target"
            $formMapper
                ->with('form.group_general')
                    ->add('linkType', 'choice_field_mask', array(
                        'choices' => array_combine(
                            $this->contentAwareFactory->getLinkTypes(),
                            $this->contentAwareFactory->getLinkTypes()
                        ),
                        'map' => array(
                            'route' => array('route'),
                            'uri' => array('uri'),
                            'content' => array('content', 'doctrine_phpcr_odm_tree'),
                        ),
                        'empty_value' => 'auto',
                    ))
                    ->add('route', 'text', array('required' => false))
                    ->add('uri', 'text', array('required' => false))
                    ->add('content', 'doctrine_phpcr_odm_tree',
                        array(
                            'root_node' => $this->contentRoot, 
                            'choice_list' => array(), 
                            'required' => false
                        )
                    )
                ->end()
            ;
        }

        // Add locale
        $formMapper
            ->with('form.group_general')
                ->add('locale', 'choice', array(
                    'choices' => array_combine($this->locales, $this->locales),
                    'empty_value' => '',
                ))
            ->end()
        ;
    }

    // is it worth configuring this.. is it ever used?
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', 'text')
            ->add('name', 'text')
            ->add('label', 'text')
            ->add('uri', 'text')
            ->add('content', 'text')
        ;
    }

    /**
     * @return MenuNode
     */
    public function getNewInstance()
    {
        /** @var $new MenuNode */
        $new = parent::getNewInstance();

        if ($this->hasRequest()) {

            // Set the parent
            $parentId = $this->getRequest()->query->get('parent');

            if (null !== $parentId) {
                $new->setParent($this->getModelManager()->find(null, $parentId));
            }

            // Set the locale
            $currentLocale = $this->getRequest()->attributes->get('_locale');

            if (in_array($currentLocale, $this->locales)) {
                $meta = $this->getModelManager()->getMetadata(get_class($new));
                $meta->setFieldValue($new, $meta->localeMapping, $currentLocale);
            }
        }

        return $new;
    }

    public function getExportFormats()
    {
        return array();
    }

    public function getContentAwareFactory() 
    {
        return $this->contentAwareFactory;
    }
    
    public function setContentAwareFactory(ContentAwareFactory $contentAwareFactory)
    {
        $this->contentAwareFactory = $contentAwareFactory;
    }
    

    public function setContentRoot($contentRoot)
    {
        $this->contentRoot = $contentRoot;
    }

    public function setMenuRoot($menuRoot)
    {
        $this->menuRoot = $menuRoot;
    }

    public function setContentTreeBlock($contentTreeBlock)
    {
        $this->contentTreeBlock = $contentTreeBlock;
    }

    /**
     * Return the content tree to show at the left, current node (or parent for new ones) selected
     *
     * @param string $position
     *
     * @return array
     */
    public function getBlocks($position)
    {
        if ('left' == $position) {
            $selected = $this->isSubjectNotNew() ? $this->getSubject()->getId() : ($this->hasRequest() ? $this->getRequest()->query->get('parent') : null);
            return array(
                array(
                    'type' => 'sonata_admin_doctrine_phpcr.tree_block',
                    'settings' => array('selected' => $selected)
                )
            );
        }
    }
}
