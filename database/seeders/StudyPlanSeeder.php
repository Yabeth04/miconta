<?php
namespace Database\Seeders;

use App\Models\StudySubject;
use Illuminate\Database\Seeder;

class StudyPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plan = [
            1 => [
                ['name' => 'Introducción al Cálculo o Matemática Básica (Colegiado)', 'status' => 'aprobado'],
                ['name' => 'Programación Básica (Introducción a la Programación)', 'status' => 'aprobado'],
                ['name' => 'Introducción a la Informática', 'status' => 'aprobado'],
                ['name' => 'Matemáticas Discretas (Colegiado)', 'status' => 'aprobado'],
            ],
            2 => [
                ['name' => 'Cálculo Diferencial e Integral I (Colegiado)'],
                ['name' => 'Introducción a la Programación (Laboratorio)', 'status' => 'aprobado'],
                ['name' => 'Fundamentos de Sistemas Operativos (Laboratorio)', 'status' => 'aprobado'],
                ['name' => 'Principios de Redes y Comunicaciones (Laboratorio)', 'status' => 'aprobado'],
                ['name' => 'Electrónica Digital y Microprocesadores', 'status' => 'en_curso'],
            ],
            3 => [
                ['name' => 'Álgebra Lineal (Colegiado)', 'status' => 'aprobado'],
                ['name' => 'Documentación de Software', 'status' => 'en_curso'],
                ['name' => 'Programación Cliente/Servidor Concurrente (Laboratorio)', 'status' => 'matriculado'],
                ['name' => 'Estructura de Datos (Laboratorio)'],
                ['name' => 'Diseño de Interfaz Gráfica de Usuario (Laboratorio)'],
            ],
            4 => [
                ['name' => 'Metodologías de Desarrollo de Proyectos', 'status' => 'matriculado'],
                ['name' => 'Fundamentos de Enrutamiento y Computación (Laboratorio)'],
                ['name' => 'Desarrollo de Aplicaciones Web y Patrones (Laboratorio)'],
                ['name' => 'Fundamentos de Diseño de Base de Datos Relacionales (Laboratorio)'],
                ['name' => 'Calidad del Software'],
            ],
            5 => [
                ['name' => 'Probabilidad y Estadística Descriptiva'],
                ['name' => 'Ambiente Web Cliente/Servidor (Laboratorio)'],
                ['name' => 'Administración de Base de Datos (Laboratorio)'],
                ['name' => 'Lenguaje de Base de Datos (Laboratorio)'],
                ['name' => 'Administración de Proyecto'],
            ],
            6 => [
                ['name' => 'Programación Avanzada (Laboratorio)'],
                ['name' => 'Data Warehouse y Base de Datos Multidimensionales (Laboratorio)'],
                ['name' => 'Análisis y Modelado de Requerimientos'],
                ['name' => 'Gobernanza y Gestión de Tecnologías de Información y Comunicaciones'],
                [
                    'name' => 'Electiva 1',
                    'is_elective_slot' => true,
                    'elective_group' => 1,
                    'elective_preferences' => [
                        'base-datos-nosql' => 'alto',
                        'arquitectura-videojuegos' => 'medio',
                    ],
                ],
            ],
            7 => [
                ['name' => 'Programación Avanzada en Web (Laboratorio)'],
                ['name' => 'Diseño y Desarrollo de Sistemas'],
                ['name' => 'Programación para Dispositivos Móviles (Laboratorio)'],
                ['name' => 'Auditoría de Sistemas'],
                [
                    'name' => 'Electiva 2',
                    'is_elective_slot' => true,
                    'elective_group' => 2,
                    'elective_preferences' => [
                        'diseno-videojuegos' => 'medio',
                        'administracion-servidores' => 'alto',
                    ],
                ],
            ],
            8 => [
                ['name' => 'Paradigmas de Programación'],
                ['name' => 'Computación y Sociedad'],
                ['name' => 'Implantación de Sistemas'],
                [
                    'name' => 'Electiva 3',
                    'is_elective_slot' => true,
                    'elective_group' => 3,
                    'selected_elective_key' => 'seguridad-informatica',
                    'elective_preferences' => [
                        'seguridad-informatica' => 'alto',
                    ],
                ],
            ],
        ];

        foreach ($plan as $termNumber => $subjects) {
            foreach ($subjects as $data) {
                StudySubject::query()->updateOrCreate(
                    [
                        'term_number' => $termNumber,
                        'name'        => $data['name'],
                    ],
                    [
                        'is_elective_slot'      => (bool) ($data['is_elective_slot'] ?? false),
                        'elective_group'        => $data['elective_group'] ?? null,
                        'status'                => $data['status'] ?? null,
                        'note'                  => $data['note'] ?? null,
                        'selected_elective_key' => $data['selected_elective_key'] ?? null,
                        'elective_preferences'  => $data['elective_preferences'] ?? null,
                    ]
                );
            }
        }
    }
}
