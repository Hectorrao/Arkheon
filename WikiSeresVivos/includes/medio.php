<?php
function medioInfo(?string $medio): array {
    $validos = ['aereo', 'terrestre', 'acuatico', 'anfibio'];
    $key = in_array($medio, $validos, true) ? $medio : 'terrestre';
    return ['key' => $key];
}