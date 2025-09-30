<?php
namespace Meduzean\EanManager\Interfaces;

defined('ABSPATH') || exit;

interface RepositoryInterface
{
    public function createOrUpdateTable(): void;
    public function insertEan(string $ean): int|false;
    public function eanExists(string $ean): int|false;
    public function getAll(int $limit = 20, int $offset = 0, string $orderby = 'ean_add_date', string $order = 'DESC', string $availability = ''): array;
    public function countAll(string $availability = ''): int;
    public function deleteById(int $id): bool;
    public function dropTable(): void;
}
