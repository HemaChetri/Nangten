<?php
/*
 * Helper -- FlashMessenger View Helper
 * chophel@athang.com 
 */
namespace Application\View\Helper;
use Zend\View\Helper\AbstractHelper;
use Zend\Mvc\Plugin\FlashMessenger\FlashMessenger;

class FlashMessage extends AbstractHelper
{  
    public function __invoke()
    {     
        $flashMessenger = new FlashMessenger();

        if($flashMessenger->hasMessages())
        {
            $flashMessage = $flashMessenger->getMessages();
            $alertMessages = "";
            foreach ($flashMessage as $message):
                $title = substr($message, 0, strpos($message, '^'));
                $message = strlen($title) > 0 ? substr($message, strpos($message, '^') + 1) : $message;
                $title = strlen($title) > 0 ? $title : 'error';
                switch($title){
                    case 'success':
                        $class = 'bg-success';
                        $icon = 'ti ti-shield-check';
                        break;
                    case 'error':
                        $class = 'bg-danger';
                        $icon = 'ti ti-shield-x';
                        break;
                    case 'warning':
                        $class = 'bg-warning';
                        $icon = 'ti ti-alert-triangle';
                        break;
                    default:
                        $class = 'bg-info';
                        $icon = 'ti ti-info-circle';
                        break;
                }
                $display_title = ucfirst($title);
                $alertMessages.="<div class='toast align-items-center ".$class." border-0' role='alert' aria-live='assertive' aria-atomic='true' data-bs-toggle='toast' data-bs-delay='4500'>
                                    <div class='d-flex'>
                                        <div class='toast-body'>
                                            <i class='icon ".$icon."'></i>
                                            <strong class='me-auto'>".$display_title."! </strong>".$message."</div>
                                        <button type='button' class='btn-close me-2 m-auto' data-bs-dismiss='toast' aria-label='Close'></button>
                                </div></div>";
            endforeach;
            echo <<<EOF
                <div class="toast-container position-absolute p-3 top-25 end-0">
                    $alertMessages
                </div>
            EOF;
        }
    }
}