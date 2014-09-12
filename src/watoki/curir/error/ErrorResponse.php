<?php
namespace watoki\curir\error;

use watoki\curir\WebRequest;
use watoki\curir\WebResponse;

class ErrorResponse extends WebResponse {

    /** @var \Exception */
    private $exception;

    /** @var WebRequest */
    private $request;

    function __construct(WebRequest $request, \Exception $exception) {
        $this->request = $request;
        $this->exception = $exception;
    }

    public function getStatus() {
        if ($this->exception instanceof HttpError) {
            return $this->exception->getStatus();
        }
        return WebResponse::STATUS_SERVER_ERROR;
    }

    public function getBody() {
        $userMessage = '';
        if ($this->exception instanceof HttpError) {
            $userMessage = $this->exception->getUserMessage();
        }

        if ($this->request->getFormats()->contains('html')) {
            $model = array(
                'status' => $this->getStatus(),
                'rootUrl' => $this->request->getContext()->toString(),
                'userMessage' => $userMessage
            );

            $details = date('Y-m-d H:i:s');
            $exception = $this->exception;
            while ($exception) {
                $details .= "\n" . get_class($exception) . ": " .  $exception->getMessage() . "\n"
                        . $exception->getTraceAsString() . "\n";
                $exception = $exception->getPrevious();
            }
            $model['details'] = htmlentities($details);

            return $this->renderTemplate($model);
        } else {
            return $userMessage ? : get_class($this->exception) . ': ' . $this->exception->getMessage();
        }
    }

    private function renderTemplate($model) {
        $template = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'error.html');
        foreach ($model as $key => $value) {
            $template = str_replace('$' . $key, $value, $template);
        }
        return $template;
    }

    /**
     * @return \Exception
     */
    public function getException() {
        return $this->exception;
    }
}