<?php

namespace App\Service;

class ResultService
{
    private $code = 0;
    private $error = false;
    private $data = [];
    private $message = "";
    private $context = [];

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @param int $code
     * @return ResultService
     */
    public function setCode(int $code): ResultService
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return ResultService
     */
    public function setData(array $data): ResultService
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return ResultService
     */
    public function setMessage(string $message): ResultService
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array $context
     * @return ResultService
     */
    public function setContext(array $context): ResultService
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->error;
    }

    /**
     * @param bool $error
     * @return ResultService
     */
    public function setError(bool $error): ResultService
    {
        $this->error = $error;
        return $this;
    }
}