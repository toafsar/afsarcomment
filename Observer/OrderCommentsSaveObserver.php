<?php
namespace Afsar\OrderComments\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class OrderCommentsSaveObserver
 * @package Afsar\OrderComments\Observer
 */
class OrderCommentsSaveObserver implements ObserverInterface
{

    /**
     * @var OrderRepositoryInterface
     */
    private $_orderRepository;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    private $file;

    /**
     * @var \Magento\Framework\Convert\ConvertArray
     */

    private $convertArray;
    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    private $converter;

    /**
     * OrderCommentsSaveObserver constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Filesystem\Io\File $file
     * @param \Magento\Framework\Convert\ConvertArray $convertArray
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $converter
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\Convert\ConvertArray $convertArray,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $converter
    )
    {
        $this->_orderRepository = $orderRepository;
        $this->file = $file;
        $this->convertArray = $convertArray;
        $this->converter = $converter;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order_id = $observer->getEvent()->getRequest()->getParam('order_id');
        $history = $observer->getEvent()->getRequest()->getParam('history');

        $_status = $history['status'];
        if($_status == 'pending'){
            $_order = $this->_orderRepository->get($order_id);
            $data = $_order->getData();
            $data = $this->converter->toNestedArray($_order, [], \Magento\Sales\Api\Data\OrderInterface::class);
            unset($data['status_histories']);
            $this->createMyXmlFile($data, 'order',$order_id);
        }
    }

    /**
     * @param array $assocArray
     * @param string $rootNodeName
     * @param string $filename
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createMyXmlFile($assocArray, $rootNodeName,$orderid)
    {
        if(!file_exists('Afsar')){
            mkdir('Afsar');
        }
        $filename = 'Afsar' . DIRECTORY_SEPARATOR . 'order-'.$orderid.'-comment.xml';
        // ConvertArray function assocToXml to create SimpleXMLElement
        $simpleXmlContents = $this->convertArray->assocToXml($assocArray,$rootNodeName);
        // convert it to xml using asXML() function
        $contents = $simpleXmlContents->asXML();
        $this->file->write($filename, $contents);
    }
}