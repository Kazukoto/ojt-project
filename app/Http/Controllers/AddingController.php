<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use app\Models\Engineer;
use app\Models\User;

class AddingController extends Controller
{
    public function addTimekeeper(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'first_name' => 'required',
                'middle_name' => 'required',
                'last_name' => 'required',
                'username' => 'required|unique:users|min:6',
                'password' => 'required|min:6',
                'password_confirmation' => 'required|same:password',
                'position' => 'required',
                'contact_number' => 'required|min:11|max:11',
                'birthdate' => 'required|format:d/m/Y',
                'gender' => 'required|on:Male,Female',
                'house_number' => 'required',
        ]);}
        if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $timekeeper = new User();
            $timekeeper->first_name = $request->first_name;
            $timekeeper->middle_name = $request->middle_name;
            $timekeeper->last_name = $request->last_name;
            $timekeeper->username = $request->username;
            $timekeeper->password = $request->password;
            $timekeeper->position = $request->position;
            $timekeeper->contact_number = $request->contact_number;
            $timekeeper->birthdate = $request->birthdate;
            $timekeeper->gender = $request->gender;
            $timekeeper->house_number = $request->house_number;
            $timekeeper->save();
        }
        
    

    public function addEngineer(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'first_name' => 'required',
                'middle_name' => 'required',
                'last_name' => 'required',
                'username' => 'required|unique:users|min:6',
                'password' => 'required|min:6',
                'password_confirmation' => 'required|same:password',
                'position' => 'required',
                'contact_number' => 'required|min:11|max:11',
                'birthdate' => 'required|format:d/m/Y',
                'gender' => 'required|on:Male,Female',
                'house_number' => 'required',
        ]);}
        if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $engineer = new User();
            $engineer->first_name = $request->first_name;
            $engineer->middle_name = $request->middle_name;
            $engineer->last_name = $request->last_name;
            $engineer->username = $request->username;
            $engineer->password = $request->password;
            $engineer->position = $request->position;
            $engineer->contact_number = $request->contact_number;
            $engineer->birthdate = $request->birthdate;
            $engineer->gender = $request->gender;
            $engineer->house_number = $request->house_number;
            $engineer->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Officer added successfully'
            ]);
            
        } 
    

    

    public function addFinance(Request $request){

    try{
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'middle_name' => 'required',
            'last_name' => 'required',
            'username' => 'required|unique:users|min:6',
            'password' => 'required|min:6',
            'password_confirmation' => 'required|same:password',
            'position' => 'required',
            'contact_number' => 'required|min:11|max:11',
            'birthdate' => 'required|format:d/m/Y',
            'gender' => 'required|on:Male,Female',
            'house_number' => 'required',
    ]);
    }
    if($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

        $finance = new User();
        $finance->first_name = $request->first_name;
        $finance->middle_name = $request->middle_name;
        $finance->last_name = $request->last_name;
        $finance->username = $request->username;
        $finance->password = $request->password;
        $finance->position = $request->position;
        $finance->contact_number = $request->contact_number;
        $finance->birthdate = $request->birthdate;
        $finance->gender = $request->gender;
        $finance->house_number = $request->house_number;
        $finance->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Officer added successfully'
        ]);
    }
    


    public function addAdmin(){
        return view('timekeeper.adding');
    }

}