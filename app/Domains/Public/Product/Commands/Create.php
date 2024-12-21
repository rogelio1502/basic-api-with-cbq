<?php

namespace App\Domains\Public\Product\Commands;

use Exception;
use Uxmal\Backend\Attributes\RegisterCommand;
use Uxmal\Backend\Command\CommandBase;
use App\Models\Product;
use Uxmal\Backend\Exception\BackendCBQException;

#[RegisterCommand('/public/products', 'post', 'cmd.public.products.create.v1')]
class Create extends CommandBase
{
    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(): array
    {
        try {
            if(!isset($this->payload['name']) || !isset($this->payload['price'])) {
                throw new BackendCBQException('Name and price are required');
            }
            $product = new Product();
            $product->name = $this->payload['name'];
            $product->price = $this->payload['price'];
            $product->save();

            return [
                'success' => true,
                'data' => $product,
            ];
        } catch (Exception $e) {

            dump($e->getMessage());

            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
