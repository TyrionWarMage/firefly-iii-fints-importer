<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

include 'Setup.php';
include 'CollectData.php';
include 'Choose2FADevice.php';
include 'Login.php';
include 'ChooseAccount.php';
include 'GetImportData.php';
include 'RunImportBatched.php';
include 'PwdInput.php';
include 'Encrypt.php';

use App\StepFunction;
use App\FinTsFactory;
use App\ConfigurationFactory;
use App\TanHandler;
use App\TransactionsToFireflySender;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Step;
use GrumpyDictator\FFIIIApiSupport\Request\GetAccountsRequest;


$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/public/html');
$twig   = new \Twig\Environment($loader);
$automate_without_js = false;

$request = Request::createFromGlobals();

if (isset($_GET['generate'])) {
    $current_step = new Step($request->request->get("step", Step::STEP_ENC0_INPUT));
} else {
    $current_step = new Step($request->request->get("step", Step::STEP0_SETUP));
}

$apcuAvailable = function_exists('apcu_enabled') && apcu_enabled();
xdebug_break();
$session = new Session();
$session->start();

if (isset($_GET['automate'])) {
    $automate_without_js = $_GET['automate'] == "true";
}




do
{
    switch ((string)$current_step) {
        case Step::STEP0_SETUP:
            $current_step = StepFunction\Setup();
            break;

        case Step::STEP1_COLLECTING_DATA:
            $current_step = StepFunction\CollectData();
            break;

        case Step::STEP1p5_CHOOSE_2FA_DEVICE:
            $current_step = StepFunction\Choose2FADevice();
            break;

        case Step::STEP2_LOGIN:
            $current_step = StepFunction\Login();
            break;

        case Step::STEP3_CHOOSE_ACCOUNT:
            $current_step = StepFunction\ChooseAccount();
            break;

        case Step::STEP4_GET_IMPORT_DATA:
            $current_step = StepFunction\GetImportData();
            break;

        case Step::STEP5_RUN_IMPORT_BATCHED:
            $current_step = StepFunction\RunImportBatched();
            break;

        case Step::STEP_ENC0_INPUT:
            $current_step = StepFunction\PwdInput();
            break;
            
        case Step::STEP_ENC1_RESULT:
            $current_step = StepFunction\Encrypt();
            break;
            
        default:
            $current_step = Step::DONE;
            if ($apcuAvailable) {
                apcu_clear_cache();
            }
            session_destroy();
            break;
    }
} while ($current_step != Step::DONE);