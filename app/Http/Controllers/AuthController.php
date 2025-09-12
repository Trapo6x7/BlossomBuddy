<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    // Inscription d'un utilisateur
        /**
         * @OA\Post(
         *     path="/api/register",
         *     summary="Inscription d'un utilisateur",
         *     tags={"Auth"},
         *     @OA\RequestBody(
         *         required=true,
         *         @OA\JsonContent(
         *             required={"prenom","nom","email","password"},
         *             @OA\Property(property="prenom", type="string", example="John"),
         *             @OA\Property(property="nom", type="string", example="Doe"),
         *             @OA\Property(property="email", type="string", example="john@example.com"),
         *             @OA\Property(property="password", type="string", example="secret")
         *         )
         *     ),
         *     @OA\Response(response=201, description="Utilisateur inscrit et token retourné", @OA\JsonContent(
         *         @OA\Property(property="access_token", type="string"),
         *         @OA\Property(property="token_type", type="string", example="Bearer")
         *     ))
         * )
         */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'prenom' => $request->prenom,
            'nom' => $request->nom,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    // Authentification d'un utilisateur
        /**
         * @OA\Post(
         *     path="/api/login",
         *     summary="Authentification utilisateur",
         *     tags={"Auth"},
         *     @OA\RequestBody(
         *         required=true,
         *         @OA\JsonContent(
         *             required={"email","password"},
         *             @OA\Property(property="email", type="string", example="john@example.com"),
         *             @OA\Property(property="password", type="string", example="secret")
         *         )
         *     ),
         *     @OA\Response(response=200, description="Token d'accès", @OA\JsonContent(
         *         @OA\Property(property="access_token", type="string"),
         *         @OA\Property(property="token_type", type="string", example="Bearer")
         *     ))
         * )
         */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

        /**
         * @OA\Post(
         *     path="/api/logout",
         *     summary="Déconnexion de l'utilisateur",
         *     tags={"Auth"},
         *     security={{"sanctum":{}}},
         *     @OA\Response(response=200, description="Déconnexion réussie", @OA\JsonContent(
         *         @OA\Property(property="message", type="string", example="Déconnexion réussie")
         *     ))
         * )
         */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie']);
    }

    /**
     * Display the specified resource.
     */
        /**
         * @OA\Get(
         *     path="/api/user/{id}",
         *     summary="Afficher un utilisateur par son id",
         *     tags={"Auth"},
         *     @OA\Parameter(
         *         name="id",
         *         in="path",
         *         required=true,
         *         @OA\Schema(type="integer")
         *     ),
         *     @OA\Response(response=200, description="Détails de l'utilisateur")
         * )
         */
    public function show(string $id)
    {
        return User::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
        /**
         * @OA\Put(
         *     path="/api/user/{id}",
         *     summary="Mettre à jour un utilisateur",
         *     tags={"Auth"},
         *     @OA\Parameter(
         *         name="id",
         *         in="path",
         *         required=true,
         *         @OA\Schema(type="integer")
         *     ),
         *     @OA\RequestBody(
         *         required=false,
         *         @OA\JsonContent(
         *             @OA\Property(property="prenom", type="string"),
         *             @OA\Property(property="nom", type="string"),
         *             @OA\Property(property="email", type="string"),
         *             @OA\Property(property="password", type="string")
         *         )
         *     ),
         *     @OA\Response(response=200, description="Utilisateur mis à jour")
         * )
         */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'prenom' => 'sometimes|string|max:255',
            'nom' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['prenom', 'nom', 'email']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);
        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     */
        /**
         * @OA\Delete(
         *     path="/api/user/{id}",
         *     summary="Supprimer un utilisateur",
         *     tags={"Auth"},
         *     @OA\Parameter(
         *         name="id",
         *         in="path",
         *         required=true,
         *         @OA\Schema(type="integer")
         *     ),
         *     @OA\Response(response=200, description="Utilisateur supprimé", @OA\JsonContent(
         *         @OA\Property(property="message", type="string", example="Utilisateur supprimé")
         *     ))
         * )
         */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé']);
    }
}
