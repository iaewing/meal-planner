<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class IngredientController extends Controller
{
    public function index(): Response
    {
        $ingredients = Ingredient::query()
            ->get();

        return Inertia::render('Ingredients/Index', [
            'ingredients' => $ingredients
        ]);
    }

    public function store(Request $request): Response | JsonResponse
    {
        if (!$request->input('name') || !$request->input('unit')) {
            return new JsonResponse([], ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }
        Ingredient::query()->create([
            'name' => $request->input('name'),
            'unit' => $request->input('unit'),
        ]);
        return Inertia::render('Ingredients/Index', [
            'ingredients' => Ingredient::query()->get()
        ]);
    }
}
