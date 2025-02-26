<?php

namespace App\Http\Controllers\Backend\Profile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Utils\Activity\SaveActivityLogController;
use App\Http\Controllers\Utils\Tools\ToolsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Config;
use File;

class ProfileController extends Controller
{
    /**
     * Validator data
     * @param mixed $data
     * @param mixed $type
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data, $type)
    {
        return Validator::make($data, [
            // Add unique validation to prevent for duplicate email while forcing unique rule to ignore a given ID
            'email' => $type == 'create' ? 'email|required|string|max:255|unique:users' : 'required|string|max:255|unique:users,email,' . $data['id'],
            // (update: not required, create: required)
            'password' => $type == 'create' ? 'required|string|min:6|max:255' : '',
            'name' => 'required|string|max:255',
        ]);
    }

    /**
     * Get named route
     * 
     * @return string
     */
    private function getRoute()
    {
        return 'profile';
    }

    /**
     * Show the form for editing the specified resource.
     * 
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function details()
    {
        $userId = Auth::user()->id;
        $data = User::find($userId);
        $data->form_action = $this->getRoute() . '.update';
        $data->button_text = 'Update';

        return view('backend.profile.form', [
            'data' => $data
        ]);
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $new = $request->all();
        try {
            $currentData = User::find($request->get('id'));
            if ($currentData) {
                $this->validator($new, 'update')->validate();

                if (!$new['password']) {
                    $new['password'] = $currentData['password'];
                } else {
                    $new['password'] = bcrypt($new['password']);
                }



                // check delete flag: [name ex: image_delete]
                if ($request->get('image_delete') != null) {
                    $new['image'] = null; // filename for db

                    if ($currentData->{'image'} != 'default-user.png') {
                        @unlink(Config::get('const.UPLOAD_PATH') . $currentData['image']);
                    }
                }

                // Handle image deletion and upload in a single block
                if ($request->get('image_delete') != null) {
                    $new['image'] = 'default-user.png'; // Set to default only if deleted

                    if ($currentData->{'image'} != 'default-user.png') {
                        $filePath = Config::get('const.UPLOAD_PATH') . $currentData['image'];
                        if (File::exists($filePath)) {
                            File::delete($filePath);
                        }
                    }
                }

                // If new image is being uploaded
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    ${'image'} = $currentData->id . "_image." . $file->getClientOriginalExtension();
                    $new['image'] = ${'image'};
                    $pathPhoto = Config::get('const.UPLOAD_PATH');
                    $file->move($pathPhoto, ${'image'});

                    // Resize photo when upload
                    $toolsController = new ToolsController();
                    $toolsController->resizePhoto($pathPhoto . $new['image'], 196);
                }

                if (!$request->hasFile('image') && $request->get('image_delete') == null) {
                    $new['image'] = $currentData['image']; // Retain old image if no new image and not deleted
                }

                // Save log
                $controller = new SaveActivityLogController();
                $controller->saveLog($new, "Update Profile");

                // Update
                $currentData->update($new);
                return redirect()->route($this->getRoute() . '.details')->with('success', Config::get('const.SUCCESS_UPDATE_MESSAGE'));
            }

            // If update is failed
            return redirect()->route($this->getRoute() . '.details')->with('error', Config::get('const.FAILED_UPDATE_MESSAGE'));
        } catch (\Exception $e) {
            // If update is failed
            return redirect()->route($this->getRoute() . '.details')->with('error', Config::get('const.FAILED_UPDATE_MESSAGE'));
        }
    }
}
