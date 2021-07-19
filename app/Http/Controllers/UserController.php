<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminCreateUserRequest;
use App\Http\Requests\AdminUserRequest;
use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\UserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Repositories\UserRepository;
use Illuminate\Validation\ValidationException;
use App\Repositories\EpochRepository;

class UserController extends Controller
{
    protected $repo, $epochRepo;

    public function __construct(UserRepository $repo, EpochRepository $epochRepo) {
        $this->repo = $repo;
        $this->epochRepo = $epochRepo;
    }

    public function getUser($address): JsonResponse {
        $user = $this->repo->getUser($address);
        if(!$user)
            return response()->json(['error'=> 'Address not found'],422);
        return response()->json($user);
    }

    public function getUser2($circle_id, $address): JsonResponse {
        $user = $this->repo->getUser2($address,$circle_id);
        if(!$user)
            return response()->json(['error'=> 'Address not found'],422);
        return response()->json($user);
    }

    public function getUsers(Request $request, $circle_id = null): JsonResponse {
        return response()->json($this->repo->getUsers($request, $circle_id));
    }

    public function createUser(AdminCreateUserRequest $request, $circle_id): JsonResponse {
        return response()->json($this->repo->createUser($request,$circle_id));
    }

    public function updateUser(UserRequest $request, $circle_id, $address): JsonResponse
    {
        $user = $request->user;
        if(!$user)
            return response()->json(['error'=> 'Address not found'],422);

        $data = $request->only('name','non_receiver','bio','epoch_first_visit');
        if($user->fixed_non_receiver ==1 ) {
            $data['non_receiver'] = 1;
        }
        $user = $this->epochRepo->removeAllPendingGiftsReceived($user, $data);
        return response()->json($user);
    }

    public function adminUpdateUser(AdminUserRequest $request, $circle_id, $address): JsonResponse
    {
        $user = $request->user;
        if(!$user)
            return response()->json(['error'=> 'Address not found'],422);
        $data = $request->only('name','address','starting_tokens','non_giver','fixed_non_receiver', 'role');

        if($data['fixed_non_receiver'] ==1 ) {
            $data['non_receiver'] = 1;
        }
        if($user->starting_tokens != $data['starting_tokens']) {
            if( $user->circle->epoches()->isActiveDate()->first()) {
                return response()->json(['error'=> 'Cannot update starting tokens during an active epoch'],422);
            } else {
                $data['give_token_remaining'] = $data['starting_tokens'];
            }
        }
        $data['address'] =  strtolower($data['address']);
        $user = $this->epochRepo->removeAllPendingGiftsReceived($user, $data);
        return response()->json($user);
    }

    public function deleteUser(DeleteUserRequest $request, $circle_id, $address) : JsonResponse  {

        $user = $request->user;
        $data = $this->repo->deleteUser($user);
        if(is_null($data)) {
            $error = ValidationException::withMessages([
                'failed' => ['Delete user failed please try again'],
            ]);
            throw $error;
        } else {
            return response()->json($data);
        }
    }

}
