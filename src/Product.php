<?php

namespace Codeplugtech\DodoPayments;

class Product
{
    public string $productId;
    public string $businessId;
    public string $name;
    public string $description;
    public string $image;
    public \DateTime $createdAt;
    public \DateTime $updatedAt;
    public bool $isRecurring;
    public string $taxCategory;
    public Price|int $price;

    public function __construct(array $data)
    {
        $this->productId = $data['product_id'];
        $this->businessId = $data['business_id'];
        $this->name = $data['name'];
        $this->description = $data['description'];
        $this->image = $data['image'];
        $this->createdAt = new \DateTime($data['created_at']);
        $this->updatedAt = new \DateTime($data['updated_at']);
        $this->isRecurring = $data['is_recurring'];
        $this->taxCategory = $data['tax_category'];
        $this->price = is_int($data['price']) ? $data['price'] : new Price($data['price']); // Instantiate Price class
    }

}
