<?php

namespace Pim\Component\Catalog\Updater\Setter;

use Akeneo\Component\FileStorage\File\FileStorerInterface;
use Akeneo\Component\FileStorage\Model\FileInfoInterface;
use Akeneo\Component\FileStorage\Repository\FileInfoRepositoryInterface;
use Pim\Component\Catalog\Builder\ProductBuilderInterface;
use Pim\Component\Catalog\Exception\InvalidArgumentException;
use Pim\Component\Catalog\FileStorage;
use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Validator\AttributeValidatorHelper;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * Sets a media value in many products
 *
 * @author    Julien Janvier <julien.janvier@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MediaAttributeSetter extends AbstractAttributeSetter
{
    /** @var FileStorerInterface */
    protected $storer;

    /** @var FileInfoRepositoryInterface */
    protected $repository;

    /**
     * @param \Pim\Component\Catalog\Builder\ProductBuilderInterface  $productBuilder
     * @param AttributeValidatorHelper $attrValidatorHelper
     * @param FileStorerInterface   $storer
     * @param FileInfoRepositoryInterface  $repository
     * @param array                    $supportedTypes
     */
    public function __construct(
        ProductBuilderInterface $productBuilder,
        AttributeValidatorHelper $attrValidatorHelper,
        FileStorerInterface $storer,
        FileInfoRepositoryInterface $repository,
        array $supportedTypes
    ) {
        parent::__construct($productBuilder, $attrValidatorHelper);

        $this->storer = $storer;
        $this->repository = $repository;
        $this->supportedTypes = $supportedTypes;
    }

    /**
     * {@inheritdoc}
     *
     * For media, we accept to have different data input format. It can be:
     *   - a key of a FileInfoInterface: "d/f/2/3/df238bdaa464e866f8fedf70531a2cc02f909dae_P1040006.JPG"
     *   - an absolute file name (e.g for imports) : "/tmp/file/mu_sky/file.jpg"
     */
    public function setAttributeData(
        ProductInterface $product,
        AttributeInterface $attribute,
        $data,
        array $options = []
    ) {
        $options = $this->resolver->resolve($options);
        $this->checkLocaleAndScope($attribute, $options['locale'], $options['scope'], 'media');
        $this->checkData($attribute, $data);

        if (null === $data) {
            $file = null;
        } elseif (null === $file = $this->repository->findOneByIdentifier($data)) {
            $file = $this->storeFile($attribute, $data);
        }

        $this->setMedia($product, $attribute, $file, $options['locale'], $options['scope']);
    }

    /**
     * Set media in the product value
     *
     * @param ProductInterface       $product
     * @param AttributeInterface     $attribute
     * @param FileInfoInterface|null $fileInfo
     * @param string|null            $locale
     * @param string|null            $scope
     */
    protected function setMedia(
        ProductInterface $product,
        AttributeInterface $attribute,
        FileInfoInterface $fileInfo = null,
        $locale = null,
        $scope = null
    ) {
        $value = $product->getValue($attribute->getCode(), $locale, $scope);
        if (null === $value) {
            $value = $this->productBuilder->addProductValue($product, $attribute, $locale, $scope);
        }

        $value->setMedia($fileInfo);
    }

    /**
     * @param AttributeInterface $attribute
     * @param mixed              $data
     */
    protected function checkData(AttributeInterface $attribute, $data)
    {
        if (null === $data) {
            return;
        }

        if (!is_string($data)) {
            throw InvalidArgumentException::stringExpected($attribute->getCode(), 'setter', 'media', gettype($data));
        }
    }

    /**
     * TODO: inform the user that this could take some time
     *
     * @param AttributeInterface $attribute
     * @param mixed              $data
     *
     * @throws InvalidArgumentException If an invalid filePath is provided
     *
     * @return FileInfoInterface|null
     */
    protected function storeFile(AttributeInterface $attribute, $data)
    {
        if (null === $data) {
            return null;
        }

        try {
            $rawFile = new \SplFileInfo($data);
            $file = $this->storer->store($rawFile, FileStorage::CATALOG_STORAGE_ALIAS);
        } catch (FileNotFoundException $e) {
            throw InvalidArgumentException::expected(
                $attribute->getCode(),
                'a valid pathname',
                'setter',
                'media',
                $data
            );
        }

        return $file;
    }
}
