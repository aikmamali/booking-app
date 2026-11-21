<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Room;

final class SaveRoom
{
    public function handle(?Room $room, string $name, ?int $capacity): Room
    {
        $room ??= new Room;
        $room->fill(['name' => trim($name), 'capacity' => $capacity])->save();

        return $room;
    }
}
