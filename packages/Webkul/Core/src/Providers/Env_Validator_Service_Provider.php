<?php

declare (strict_types=1);
namespace Webkul\Core\Providers;

use Dotenv\Exception\Invalid_File_Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Service_Provider;
use Symfony\Component\Console\Output\Console_Output;
class Env_Validator_Service_Provider extends Service_Provider
{
    /**
     * Set environment variable rules.
     *
     * @var array
     */
    protected $rules = ['DB_PREFIX' => 'not_regex:/[^A-Za-z0-9_]/'];
    /**
     * Set environment variable error messages.
     *
     * @var array
     */
    protected $messages = ['not_regex' => 'DB_PREFIX ENV is not valid.'];
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->validate_env_variables();
    }
    /**
     * Validate environment variables.
     *
     * @return void
     */
    private function validate_env_variables()
    {
        $validator = Validator::make($_ENV, $this->rules, $this->messages);
        if ($validator->fails()) {
            $error_key = collect($validator->errors()->keys())->first();
            $error_value = env($error_key);
            $this->write_error_and_die(new Invalid_File_Exception($this->get_error_message('some invalid values', $error_value)));
        }
    }
    /**
     * Generate a friendly error message.
     *
     * @param  string  $cause
     * @param  string  $subject
     * @return string
     */
    private function get_error_message($cause, $subject)
    {
        return sprintf('Failed to parse dotenv file due to %s. Failed at [%s].', $cause, strtok($subject, "\n"));
    }
    /**
     * Write the error information to the screen and exit.
     *
     * @return void
     */
    private function write_error_and_die(Invalid_File_Exception $e)
    {
        $output = (new Console_Output())->get_error_output();
        $output->writeln('The environment file is invalid!');
        $output->writeln($e->get_message());
        exit(1);
    }
}