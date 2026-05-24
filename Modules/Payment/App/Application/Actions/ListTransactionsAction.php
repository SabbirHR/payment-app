<?php
namespace Modules\Payment\App\Application\Actions;

use Modules\Payment\App\Domain\Contracts\TransactionRepository;

class ListTransactionsAction
{
    protected TransactionRepository $repository;

    public function __construct(TransactionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute()
    {
        // Placeholder for listing logic, pagination etc.
        // E.g. return $this->repository->paginate();
        return [];
    }
}
