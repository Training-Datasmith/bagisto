<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\Json_Response;
use Webkul\Magic_Ai\Facades\Magic_Ai;
class Magic_Ai_Controller extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function content(): Json_Response
    {
        $this->validate(request(), ['model' => 'required', 'prompt' => 'required']);
        try {
            $response = Magic_Ai::set_model(request()->input('model'))->set_prompt(request()->input('prompt'))->ask();
            return new Json_Response(['content' => $response]);
        } catch (\Exception $e) {
            return new Json_Response(['message' => $e->get_message()], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function image(): Json_Response
    {
        config(['openai.api_key' => core()->get_config_data('general.magic_ai.settings.api_key'), 'openai.organization' => core()->get_config_data('general.magic_ai.settings.organization')]);
        $this->validate(request(), ['prompt' => 'required', 'model' => 'required|in:dall-e-2,dall-e-3', 'n' => 'required_if:model,dall-e-2|integer|min:1|max:10', 'size' => 'required|in:1024x1024,1024x1792,1792x1024', 'quality' => 'required_if:model,dall-e-3|in:standard,hd']);
        try {
            $options = request()->only(['n', 'size', 'quality']);
            $images = Magic_Ai::set_model(request()->input('model'))->set_prompt(request()->input('prompt'))->images($options);
            return new Json_Response(['images' => $images]);
        } catch (\Exception $e) {
            return new Json_Response(['message' => $e->get_message()], 500);
        }
    }
}