<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/products",
     *      operationId="getProductsList",
     *      tags={"Products"},
     *      summary="Get list of all products",
     *      description="Returns a paginated list of all products, including their type-specific attributes.",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(ref="#/components/schemas/ProductResource")
     *          )
     *      ),
     *      @OA\Response(response=500, description="Server error"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index()
    {
        // Using paginate() is generally better for performance on large datasets
        return ProductResource::collection(Product::paginate(15));
    }

    /**
     * @OA\Get(
     *      path="/api/products/{product}",
     *      operationId="getProductById",
     *      tags={"Products"},
     *      summary="Get a single product's details",
     *      description="Returns the details of a single product by its ID. Uses route-model binding.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="product",
     *          description="Product ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ProductResource")
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found",
     *      @OA\Response(response=401, description="Unauthenticated")
     *      )
     * )
     *
     * @param  \App\Models\Product  $product
     * @return \App\Http\Resources\ProductResource
     */
    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    /**
     * @OA\Post(
     *      path="/api/products",
     *      operationId="storeProduct",
     *      tags={"Products"},
     *      summary="Create a new product",
     *      description="Creates a new product. Requires authentication.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Product data",
     *          @OA\JsonContent(
     *              required={"name", "description", "type"},
     *              @OA\Property(property="name", type="string", example="New Gaming PC"),
     *              @OA\Property(property="description", type="string", example="A very fast gaming PC."),
     *              @OA\Property(property="type", type="string", enum={"fixed_price", "auction"}, example="fixed_price"),
     *              @OA\Property(property="price", type="number", format="float", example=1999.99, description="Required if type is 'fixed_price'."),
     *              @OA\Property(property="stock_quantity", type="integer", example=50, description="Required if type is 'fixed_price'."),
     *              @OA\Property(property="starting_price", type="number", format="float", example=1500.00, description="Required if type is 'auction'."),
     *              @OA\Property(property="auction_end_time", type="string", format="date-time", example="2025-01-01T00:00:00Z", description="Required if type is 'auction'."),
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Product created successfully",
     *          @OA\JsonContent(ref="#/components/schemas/ProductResource")
     *      ),
     *      @OA\Response(response=422, description="Validation error"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->validated();

        // For a new auction, the starting bid is the current highest bid.
        if ($validatedData['type'] === ProductType::Auction->value) {
            $validatedData['current_highest_bid'] = $validatedData['starting_price'];
        }

        $product = Product::create($validatedData);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * @OA\Put(
     *      path="/api/products/{product}",
     *      operationId="updateProduct",
     *      tags={"Products"},
     *      summary="Update an existing product",
     *      description="Updates a product's details. Requires authentication.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="product",
     *          description="Product ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Product data to update. Note: changing 'type' is generally not recommended and may require sending all relevant fields for the new type.",
     *          @OA\JsonContent(ref="#/components/schemas/ProductResource")
     *      ),
     *      @OA\Response(response=200, description="Product updated successfully", @OA\JsonContent(ref="#/components/schemas/ProductResource")),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=422, description="Validation error"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $validatedData = $request->validated();
        $product->update($validatedData);

        return new ProductResource($product->fresh());
    }

    /**
     * @OA\Delete(
     *      path="/api/products/{product}",
     *      operationId="deleteProduct",
     *      tags={"Products"},
     *      summary="Delete a product",
     *      description="Deletes a product by its ID. Requires authentication.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="product",
     *          description="Product ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(response=204, description="No Content"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(Product $product)
    {
        // Add authorization check here if needed, e.g., using a Policy
        // $this->authorize('delete', $product);

        $product->delete();

        return response()->noContent();
    }
}
