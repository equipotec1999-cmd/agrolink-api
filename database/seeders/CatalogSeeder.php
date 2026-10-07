<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\ProductType;
use Illuminate\Database\Seeder;

/**
 * Siembra el mismo catálogo que ya vive en el prototipo Flutter
 * (lib/features/catalog/data/mock/mock_catalog.dart), para que app y backend queden
 * idénticos desde el día uno (pendiente de Fase 2, ahora resuelto).
 *
 * DESVIACIÓN encontrada y corregida al escribir este seeder (regla §20: "si una idea es
 * mala, decirlo y proponer alternativa"): en el prototipo Flutter varios atributos con el
 * mismo nombre ("raza", "sexo", "proposito", "estado_sanitario", "genetica", "presentacion")
 * tienen listas de OPCIONES DISTINTAS según el tipo de producto (p.ej. "raza" en Caballos
 * trae "Cuarto de Milla, Azteca..." y en Bovinos trae "Brahman, Suizo..."). Eso es válido en
 * Flutter porque cada tipo trae su propia lista suelta, pero en Postgres `attributes.attr_key`
 * es único globalmente y las opciones cuelgan de un solo `attribute_id`. Un único atributo
 * "raza" no puede tener dos listas de opciones distintas a la vez.
 * Solución: para esos atributos con opciones específicas por tipo, la clave interna en BD
 * lleva un sufijo del tipo de producto (p.ej. "raza_equino", "raza_bovino"), pero el
 * `label` visible sigue siendo el mismo ("Raza") — el usuario nunca ve la clave interna.
 * Los atributos verdaderamente compartidos (mismas opciones en Flutter, p.ej. los 5 de
 * "salud" en animales, sexo/peso/edad en bovinos-ovinos-caprinos-porcinos) sí se
 * reutilizan como un solo registro, tal como en el prototipo.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'animales' => Category::updateOrCreate(['slug' => 'animales'], [
                'nombre' => 'Animales', 'clave_color' => 'animales', 'icono' => 'cow', 'orden' => 1,
            ]),
            'apicultura' => Category::updateOrCreate(['slug' => 'apicultura'], [
                'nombre' => 'Apicultura', 'clave_color' => 'apicultura', 'icono' => 'bee', 'orden' => 2,
            ]),
            'agricultura' => Category::updateOrCreate(['slug' => 'agricultura'], [
                'nombre' => 'Agricultura', 'clave_color' => 'agricultura', 'icono' => 'chili', 'orden' => 3,
            ]),
        ];

        // ── Atributos compartidos entre varios tipos de producto (idénticos en Flutter) ──
        $salud = [
            'vacunacion' => ['Vacunación', 'select', null, 'salud', true, ['Al corriente', 'Parcial', 'Sin registro']],
            'desparasitacion' => ['Última desparasitación', 'date', null, 'salud', false, null],
            'estado_sanitario' => ['Estado sanitario', 'select', null, 'salud', true, ['Sano', 'En tratamiento', 'En observación']],
            'enfermedades' => ['Enfermedades conocidas', 'text', null, 'salud', false, null],
            'tratamientos' => ['Tratamientos', 'text', null, 'salud', false, null],
        ];
        // Formato largo directo (no pasa por withGroup): [label, type, unit, group, required, filterable, options].
        $cosecha = [
            'variedad' => ['Variedad', 'text', null, 'general', true, true, null],
            'metodo_cultivo' => ['Método de cultivo', 'select', null, 'general', true, true, ['Convencional', 'Orgánico (sin certificar)', 'Orgánico certificado']],
            'sistema' => ['Sistema', 'select', null, 'general', false, false, ['Campo abierto', 'Invernadero', 'Malla sombra']],
            'fecha_cosecha' => ['Fecha de cosecha', 'date', null, 'general', true, false, null],
            'calidad' => ['Calidad', 'select', null, 'general', true, true, ['Primera', 'Segunda', 'Tercera']],
            'madurez' => ['Madurez', 'select', null, 'general', false, false, ['Verde', 'Pintón', 'Maduro']],
            'presentacion_cosecha' => ['Presentación', 'select', null, 'comercial', false, false, ['Granel', 'Caja', 'Arpilla', 'Tonelada']],
            'pedido_minimo' => ['Pedido mínimo', 'number', 'kg', 'comercial', false, false, null],
        ];

        $types = [
            'equinos' => [
                'categoria' => 'animales', 'nombre' => 'Caballos', 'icono' => 'horse', 'dias' => 45,
                'attrs' => [
                    'raza_equino' => ['Raza', 'select', null, 'general', true, true, ['Cuarto de Milla', 'Azteca', 'Pura Sangre', 'Criollo', 'Appaloosa', 'Otra']],
                    'sexo_equino' => ['Sexo', 'select', null, 'general', true, true, ['Macho', 'Hembra', 'Macho castrado']],
                    'edad_anios' => ['Edad', 'number', 'años', 'general', true, true, null, 0, 30],
                    'peso' => ['Peso', 'number', 'kg', 'general', false, false, null],
                    'altura' => ['Altura a la cruz', 'number', 'm', 'general', false, false, null],
                    'color' => ['Color / capa', 'text', null, 'general', false, false, null],
                    'disciplina' => ['Disciplina', 'select', null, 'general', false, true, ['Rienda', 'Charrería', 'Trabajo de campo', 'Paseo', 'Carreras', 'Salto']],
                    'entrenamiento' => ['Nivel de entrenamiento', 'select', null, 'general', false, false, ['Sin domar', 'Básico', 'Intermedio', 'Avanzado']],
                    'temperamento' => ['Temperamento', 'select', null, 'general', false, false, ['Dócil', 'Moderado', 'Enérgico']],
                    'genealogia' => ['Genealogía', 'text', null, 'reproduccion', false, false, null],
                    'estado_reproductivo' => ['Estado reproductivo', 'select', null, 'reproduccion', false, false, ['No aplica', 'Vacía', 'Gestante', 'Lactando', 'Semental activo']],
                    ...$this->withGroup($salud),
                ],
            ],
            'bovinos' => [
                'categoria' => 'animales', 'nombre' => 'Bovinos', 'icono' => 'cow', 'dias' => 45,
                'attrs' => [
                    'raza_bovino' => ['Raza', 'select', null, 'general', true, true, ['Brahman', 'Suizo', 'Brahman x Suizo', 'Nelore', 'Gyr', 'Holstein', 'Charolais', 'Angus', 'Otra']],
                    'sexo' => ['Sexo', 'select', null, 'general', true, true, ['Macho', 'Hembra']],
                    'edad_meses' => ['Edad', 'number', 'meses', 'general', true, true, null, 0, 120],
                    'peso' => ['Peso', 'number', 'kg', 'general', true, true, null, 0, 900],
                    'proposito_bovino' => ['Propósito', 'select', null, 'general', true, true, ['Engorda', 'Cría', 'Leche', 'Doble propósito', 'Pie de cría']],
                    'arete' => ['Identificación / arete', 'text', null, 'general', false, false, null],
                    'condicion_corporal' => ['Condición corporal', 'select', null, 'general', false, false, ['1', '2', '3', '4', '5']],
                    'estado_reproductivo' => ['Estado reproductivo', 'select', null, 'reproduccion', false, false, ['No aplica', 'Vacía', 'Gestante', 'Lactando', 'Semental activo']],
                    'partos' => ['Número de partos', 'number', null, 'reproduccion', false, false, null],
                    'leche_litros' => ['Producción de leche', 'number', 'L/día', 'produccion', false, false, null],
                    ...$this->withGroup($salud),
                ],
            ],
            'ovinos' => [
                'categoria' => 'animales', 'nombre' => 'Ovinos', 'icono' => 'sheep', 'dias' => 45,
                'attrs' => [
                    'raza_ovino' => ['Raza', 'select', null, 'general', true, true, ['Pelibuey', 'Katahdin', 'Dorper', 'Blackbelly', 'Cruza']],
                    'sexo' => ['Sexo', 'select', null, 'general', true, true, ['Macho', 'Hembra']],
                    'edad_meses' => ['Edad', 'number', 'meses', 'general', true, true, null, 0, 120],
                    'peso' => ['Peso', 'number', 'kg', 'general', true, true, null, 0, 900],
                    'proposito_ovino' => ['Propósito', 'select', null, 'general', true, true, ['Engorda', 'Pie de cría', 'Reproductor']],
                    'condicion_corporal' => ['Condición corporal', 'select', null, 'general', false, false, ['1', '2', '3', '4', '5']],
                    'estado_reproductivo' => ['Estado reproductivo', 'select', null, 'reproduccion', false, false, ['No aplica', 'Vacía', 'Gestante', 'Lactando', 'Semental activo']],
                    'partos' => ['Número de partos', 'number', null, 'reproduccion', false, false, null],
                    ...$this->withGroup($salud),
                ],
            ],
            'caprinos' => [
                'categoria' => 'animales', 'nombre' => 'Caprinos', 'icono' => 'goat', 'dias' => 45,
                'attrs' => [
                    'raza_caprino' => ['Raza', 'select', null, 'general', true, true, ['Saanen', 'Alpina', 'Nubia', 'Boer', 'Criolla']],
                    'sexo' => ['Sexo', 'select', null, 'general', true, true, ['Macho', 'Hembra']],
                    'edad_meses' => ['Edad', 'number', 'meses', 'general', true, true, null, 0, 120],
                    'peso' => ['Peso', 'number', 'kg', 'general', true, true, null, 0, 900],
                    'proposito_caprino' => ['Propósito', 'select', null, 'general', true, true, ['Leche', 'Carne', 'Pie de cría']],
                    'leche_litros' => ['Producción de leche', 'number', 'L/día', 'produccion', false, false, null],
                    'estado_reproductivo' => ['Estado reproductivo', 'select', null, 'reproduccion', false, false, ['No aplica', 'Vacía', 'Gestante', 'Lactando', 'Semental activo']],
                    'partos' => ['Número de partos', 'number', null, 'reproduccion', false, false, null],
                    ...$this->withGroup($salud),
                ],
            ],
            'porcinos' => [
                'categoria' => 'animales', 'nombre' => 'Porcinos', 'icono' => 'pig', 'dias' => 45,
                'attrs' => [
                    'raza_genetica_porcino' => ['Raza / genética', 'text', null, 'general', true, false, null],
                    'sexo' => ['Sexo', 'select', null, 'general', true, true, ['Macho', 'Hembra']],
                    'edad_meses' => ['Edad', 'number', 'meses', 'general', true, true, null, 0, 120],
                    'peso' => ['Peso', 'number', 'kg', 'general', true, true, null, 0, 900],
                    'proposito_porcino' => ['Propósito', 'select', null, 'general', true, true, ['Engorda', 'Pie de cría', 'Semental']],
                    'alimentacion' => ['Alimentación', 'select', null, 'general', false, false, ['Alimento balanceado', 'Mixta', 'Traspatio']],
                    ...$this->withGroup($salud),
                ],
            ],
            'colmenas' => [
                'categoria' => 'apicultura', 'nombre' => 'Colmenas', 'icono' => 'hive', 'dias' => 30,
                'attrs' => [
                    'tipo_colmena' => ['Tipo de colmena', 'select', null, 'general', true, true, ['Langstroth', 'Jumbo', 'Dadant']],
                    'alzas' => ['Número de alzas', 'number', null, 'general', true, false, null],
                    'bastidores' => ['Bastidores ocupados', 'number', null, 'general', true, false, null],
                    'bastidores_cria' => ['Bastidores con cría', 'number', null, 'general', false, false, null],
                    'genetica' => ['Genética / línea', 'select', null, 'general', false, true, ['Italiana', 'Carniola', 'Africanizada', 'Local']],
                    'fuerza' => ['Fuerza de colonia', 'select', null, 'general', true, false, ['Débil', 'Media', 'Fuerte']],
                    'edad_reina' => ['Edad de la reina', 'number', 'meses', 'general', false, false, null],
                    'ultima_inspeccion' => ['Última inspección', 'date', null, 'salud', false, false, null],
                    'estado_sanitario_colmena' => ['Estado sanitario', 'select', null, 'salud', true, false, ['Sana', 'En tratamiento', 'En observación']],
                ],
            ],
            'nucleos' => [
                'categoria' => 'apicultura', 'nombre' => 'Núcleos', 'icono' => 'bee', 'dias' => 30,
                'attrs' => [
                    'bastidores' => ['Bastidores', 'number', null, 'general', true, false, null],
                    'bastidores_cria' => ['Bastidores con cría', 'number', null, 'general', true, false, null],
                    'reina_fecundada' => ['Reina fecundada', 'boolean', null, 'general', true, false, null],
                    'genetica' => ['Genética / línea', 'select', null, 'general', false, true, ['Italiana', 'Carniola', 'Africanizada', 'Local']],
                ],
            ],
            'reinas' => [
                'categoria' => 'apicultura', 'nombre' => 'Abejas reina', 'icono' => 'crown', 'dias' => 30,
                'attrs' => [
                    'genetica' => ['Genética / línea', 'select', null, 'general', true, true, ['Italiana', 'Carniola', 'Africanizada', 'Local']],
                    'fecundada' => ['Fecundada', 'boolean', null, 'general', true, false, null],
                    'marcada' => ['Marcada', 'boolean', null, 'general', false, false, null],
                ],
            ],
            'miel' => [
                'categoria' => 'apicultura', 'nombre' => 'Miel', 'icono' => 'honey', 'dias' => 60,
                'attrs' => [
                    'tipo_miel' => ['Tipo de miel', 'select', null, 'general', true, true, ['Multifloral', 'Monofloral', 'Cremosa']],
                    'origen_floral' => ['Origen floral', 'text', null, 'general', true, false, null],
                    'fecha_cosecha' => ['Fecha de cosecha', 'date', null, 'general', true, false, null],
                    'lote' => ['Lote', 'text', null, 'general', false, false, null],
                    'extraccion' => ['Método de extracción', 'select', null, 'general', false, false, ['Centrífuga', 'Prensado']],
                    'presentacion_miel' => ['Presentación', 'select', null, 'comercial', true, false, ['Granel (tambo)', 'Cubeta', 'Frasco 1 kg', 'Frasco 500 g']],
                    'pedido_minimo' => ['Pedido mínimo', 'number', 'kg', 'comercial', false, false, null],
                ],
            ],
            'chiles' => [
                'categoria' => 'agricultura', 'nombre' => 'Chiles', 'icono' => 'chili', 'dias' => 20,
                'attrs' => $cosecha,
            ],
            'frutas' => [
                'categoria' => 'agricultura', 'nombre' => 'Frutas', 'icono' => 'fruit', 'dias' => 20,
                'attrs' => $cosecha,
            ],
            'hortalizas' => [
                'categoria' => 'agricultura', 'nombre' => 'Hortalizas', 'icono' => 'leaf', 'dias' => 20,
                'attrs' => $cosecha,
            ],
            'plantas' => [
                'categoria' => 'agricultura', 'nombre' => 'Plantas', 'icono' => 'sprout', 'dias' => 60,
                'attrs' => [
                    'especie' => ['Especie', 'text', null, 'general', true, true, null],
                    'variedad' => ['Variedad', 'text', null, 'general', true, false, null],
                    'edad_meses' => ['Edad', 'number', 'meses', 'general', false, false, null],
                    'altura_cm' => ['Altura', 'number', 'cm', 'general', true, false, null],
                    'contenedor' => ['Contenedor', 'select', null, 'general', true, false, ['Bolsa', 'Maceta', 'Charola', 'Raíz desnuda']],
                    'injertada' => ['Injertada', 'boolean', null, 'general', false, true, null],
                    'patron' => ['Patrón', 'text', null, 'general', false, false, null],
                ],
            ],
        ];

        foreach ($types as $slug => $def) {
            $productType = ProductType::updateOrCreate(['slug' => $slug], [
                'categoria_id' => $categories[$def['categoria']]->id,
                'nombre' => $def['nombre'],
                'icono' => $def['icono'],
                'dias_vigencia_predeterminados' => $def['dias'],
                'orden' => 0,
            ]);

            $sort = 0;
            foreach ($def['attrs'] as $key => $spec) {
                // [label, data_type, unit, group, required, filterable, options, min?, max?]
                [$label, $dataType, $unit, $group, $required, $filterable, $options] = $spec;
                $min = $spec[7] ?? null;
                $max = $spec[8] ?? null;

                $attribute = Attribute::updateOrCreate(['clave' => $key], [
                    'etiqueta' => $label,
                    'tipo_dato' => $dataType,
                    'unidad' => $unit,
                    'grupo' => $group,
                ]);

                if ($dataType === 'select' && $options) {
                    foreach ($options as $i => $value) {
                        $attribute->options()->updateOrCreate(['valor' => $value], ['orden' => $i]);
                    }
                }

                $productType->attributes()->syncWithoutDetaching([
                    $attribute->id => [
                        'es_obligatorio' => $required,
                        'es_filtrable' => $filterable,
                        'orden' => $sort++,
                        'valor_minimo' => $min,
                        'valor_maximo' => $max,
                    ],
                ]);
            }
        }
    }

    /**
     * Normaliza las listas cortas [label, data_type, unit, group, filterable, options] de los
     * grupos compartidos ($salud, $cosecha) al formato largo [label, data_type, unit, group,
     * required, filterable, options] que usan los tipos de producto.
     */
    private function withGroup(array $shared): array
    {
        $out = [];
        foreach ($shared as $key => [$label, $dataType, $unit, $group, $required, $options]) {
            $out[$key] = [$label, $dataType, $unit, $group, $required, false, $options];
        }

        return $out;
    }
}
