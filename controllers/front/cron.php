<?php
/**
 * PostFinance Checkout Prestashop
 *
 * This Prestashop module enables to process payments with PostFinance Checkout (https://www.postfinance.ch).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2019 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

class PostFinanceCheckoutCronModuleFrontController extends ModuleFrontController
{

    public $display_column_left = false;

    public $ssl = true;

    public function postProcess()
    {
        ob_end_clean();
        // Return request but keep executing
        set_time_limit(0);
        ignore_user_abort(true);
        ob_start();
        if (session_id()) {
            session_write_close();
        }
        header("Content-Encoding: none");
        header("Connection: close");
        header('Content-Type: image/png');
        header("Content-Length: 0");
        ob_end_flush();
        flush();
        if (is_callable('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        
        $securityToken = Tools::getValue('security_token', false);
        if (! $securityToken) {
            die();
        }
        $time = new DateTime();
        PostFinanceCheckout_Helper::startDBTransaction();
        try {
            $sqlUpdate = 'UPDATE ' . _DB_PREFIX_ . 'pfc_cron_job SET constraint_key = 0, state = "' .
                 pSQL(PostFinanceCheckout_Cron::STATE_PROCESSING) . '" , date_started = "' .
                 pSQL($time->format('Y-m-d H:i:s')) . '" WHERE security_token = "' . pSQL(
                     $securityToken
                 ) . '" AND state = "' . pSQL(PostFinanceCheckout_Cron::STATE_PENDING) . '"';
            
            $updateResult = DB::getInstance()->execute($sqlUpdate, false);
            if (! $updateResult) {
                $code = DB::getInstance()->getNumberError();
                if ($code == PostFinanceCheckout::MYSQL_DUPLICATE_CONSTRAINT_ERROR_CODE) {
                    PostFinanceCheckout_Helper::rollbackDBTransaction();
                    // Another Cron already running
                    die();
                } else {
                    PostFinanceCheckout_Helper::rollbackDBTransaction();
                    PrestaShopLogger::addLog(
                        'Could not update cron job. ' . DB::getInstance()->getMsgError(),
                        2,
                        null,
                        'PostFinanceCheckout'
                    );
                    die();
                }
            }
            if (DB::getInstance()->Affected_Rows() == 0) {
                // Simultaneous Request
                PostFinanceCheckout_Helper::commitDBTransaction();
                die();
            }
        } catch (PrestaShopDatabaseException $e) {
            $code = DB::getInstance()->getNumberError();
            if ($code == PostFinanceCheckout::MYSQL_DUPLICATE_CONSTRAINT_ERROR_CODE) {
                PostFinanceCheckout_Helper::rollbackDBTransaction();
                // Another Cron already running
                die();
            } else {
                PostFinanceCheckout_Helper::rollbackDBTransaction();
                PrestaShopLogger::addLog(
                    'Could not update cron job. ' . DB::getInstance()->getMsgError(),
                    2,
                    null,
                    'PostFinanceCheckout'
                );
                die();
            }
        }
        PostFinanceCheckout_Helper::commitDBTransaction();
        
        // We reduce max running time, so th cron has time to clean up.
        $maxTime = $time->format("U");
        $maxTime += PostFinanceCheckout_Cron::MAX_RUN_TIME_MINUTES * 60 - 60;
        
        $tasks = Hook::exec("postFinanceCheckoutCron", array(), null, true, false);
        $error = array();
        foreach ($tasks as $module => $subTasks) {
            foreach ($subTasks as $subTask) {
                if ($maxTime - 15 < time()) {
                    $error[] = "Cron overloaded could not execute all registered tasks.";
                    break 2;
                }
                $callableName = null;
                if (! is_callable($subTask, false, $callableName)) {
                    $error[] = "Module '$module' returns not callable task '$callableName'.";
                    continue;
                }
                try {
                    call_user_func($subTask, $maxTime);
                } catch (Exception $e) {
                    $error[] = "Module '$module' does not handle all exceptions in task '$callableName'. Exception Message: " .
                         $e->getMessage();
                }
                if ($maxTime + 15 < time()) {
                    $error[] = "Module '$module' returns not callable task '$callableName' does not respect the max runtime.";
                    break 2;
                }
            }
        }
        PostFinanceCheckout_Helper::startDBTransaction();
        try {
            $status = PostFinanceCheckout_Cron::STATE_SUCCESS;
            $errorMessage = "";
            if (! empty($error)) {
                $status = PostFinanceCheckout_Cron::STATE_ERROR;
                $errorMessage = implode("\n", $error);
            }
            $endTime = new DateTime();
            $sqlUpdate = 'UPDATE ' . _DB_PREFIX_ . 'pfc_cron_job SET constraint_key = id_cron_job, state = "' .
                 pSQL($status) . '" , date_finished = "' . pSQL($endTime->format('Y-m-d H:i:s')) .
                 '", error_msg = "'.pSQL($errorMessage).'" WHERE security_token = "' . pSQL($securityToken) . '" AND state = "' .
                 pSQL(PostFinanceCheckout_Cron::STATE_PROCESSING) . '"';
            
            $updateResult = DB::getInstance()->execute($sqlUpdate, false);
            if (! $updateResult) {
                PostFinanceCheckout_Helper::rollbackDBTransaction();
                PrestaShopLogger::addLog(
                    'Could not update finished cron job. ' . DB::getInstance()->getMsgError(),
                    2,
                    null,
                    'PostFinanceCheckout'
                );
                die();
            }
            PostFinanceCheckout_Helper::commitDBTransaction();
        } catch (PrestaShopDatabaseException $e) {
            PostFinanceCheckout_Helper::rollbackDBTransaction();
            PrestaShopLogger::addLog(
                'Could not update finished cron job. ' . DB::getInstance()->getMsgError(),
                2,
                null,
                'PostFinanceCheckout'
            );
            die();
        }
        PostFinanceCheckout_Cron::insertNewPendingCron();
        die();
    }

    public function setMedia()
    {
        // We do not need styling here
    }

    protected function displayMaintenancePage()
    {
        // We never display the maintenance page.
    }

    protected function displayRestrictedCountryPage()
    {
        // We do not want to restrict the content by any country.
    }

    protected function canonicalRedirection($canonical_url = '')
    {
        // We do not need any canonical redirect
    }
}
