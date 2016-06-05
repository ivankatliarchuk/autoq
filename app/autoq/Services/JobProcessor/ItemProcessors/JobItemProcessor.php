<?php


namespace Autoq\Services\JobProcessor\ItemProcessors;

use Autoq\Services\JobProcessor\JobProcessorErrors;
use Phalcon\Validation\Message;
use Phalcon\Validation\Message\Group;

abstract class JobItemProcessor
{
    protected $fieldName;

    private $rawData;
    private $sanitizedData;
    private $messages;

    private $isValid = false;

    /**
     * JobNameProcessor constructor.
     * @param $rawData
     */
    public function __construct($rawData)
    {
        $this->rawData = $rawData;
        $this->messages = new Group();
        $this->sanitizedData = null;

        $this->sanitizedData = $this->sanitize($rawData);

        $this->validate($this->sanitizedData);
        
        $this->isValid = $this->messages->count() == 0;
    }

    /**
     * @return mixed
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * @return mixed
     */
    public function getSanitizedData()
    {
        $this->sanitizedData;
    }

    /**
     * @param $data
     * @return mixed
     */
    abstract protected function sanitize($data);

    /**
     * @param $data
     * @return mixed
     */
    abstract protected function validate($data);

    /**
     * @return mixed
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param $code
     */
    protected function addMessageByCode($code)
    {
        $this->messages->appendMessage(JobProcessorErrors::asMessageObject($code,$this->getFieldName()));
    }

    /**
     * @param Group $messages
     */
    protected function appendMessages(Group $messages)
    {

        foreach ($messages as $message) {
            $this->messages->appendMessage($message);
        }

    }

    /**
     * @return mixed
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param mixed $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @return bool|mixed
     */
    public function getIsValid()
    {
        return $this->isValid;
    }


}