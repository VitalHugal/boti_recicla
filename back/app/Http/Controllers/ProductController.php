<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Service\Utils;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    protected $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function getAllProducts(Request $request)
    {
        try {
            $products = Product::when(
                $request->has('credits') && ($request->input('credits') != ''),
                function ($query) use ($request) {
                    $query->where('credit', '<=', $request->input('credits'));
                }
            )
                ->orderBy('credit', 'desc')
                ->get();

            if ($request->has('credits') && $request->input('credits') != '') {
                $products = $products->filter(function ($product) {

                    $productQuantities = Utils::getProductQuantities($product->id);

                    $qtdProduct = $productQuantities['qtdProduct'];
                    $qtdExits = $productQuantities['qtdExits'];

                    return ($qtdProduct - $qtdExits) > 0;
                })->values(); // reorganiza os índices
            }

            $products = $products->transform(function ($product) {

                $productQuantities = Utils::getProductQuantities($product->id);

                $qtdProduct = $productQuantities['qtdProduct'];
                $qtdExits = $productQuantities['qtdExits'];

                $quantityTotalProduct = $qtdProduct - $qtdExits;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'credit' => $product->credit,
                    'quantity' => $quantityTotalProduct,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Produtos recuperados com sucesso.',
                'data' => $products,
            ]);
        } catch (ValidationException $ve) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $ve->errors(),
            ]);
        } catch (QueryException $qe) {

            Log::info('Error DB: ' . $qe->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo inesperado aconteceu. Tente novamente mais tarde.',
            ]);
        } catch (Exception $e) {

            Log::info('Error: ' . $e->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo aconteceu. Tente novamente mais tarde.',
            ]);
        }
    }
}
