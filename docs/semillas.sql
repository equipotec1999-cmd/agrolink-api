--
-- PostgreSQL database dump
--

\restrict UjVbPfHYlpBk45UASkXTsfodTUNDC9Nc6ZuuCCovD5pBl8mGiWJZd8PdHACmf3z

-- Dumped from database version 16.4 (Debian 16.4-1.pgdg110+2)
-- Dumped by pg_dump version 16.15 (Ubuntu 16.15-1.pgdg24.04+2)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: atributos; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.atributos VALUES (1, 'raza_equino', 'Raza', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (2, 'sexo_equino', 'Sexo', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (3, 'edad_anios', 'Edad', 'number', 'años', 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (4, 'peso', 'Peso', 'number', 'kg', 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (5, 'altura', 'Altura a la cruz', 'number', 'm', 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (6, 'color', 'Color / capa', 'text', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (7, 'disciplina', 'Disciplina', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (8, 'entrenamiento', 'Nivel de entrenamiento', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (9, 'temperamento', 'Temperamento', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (10, 'genealogia', 'Genealogía', 'text', NULL, 'reproduccion', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (11, 'estado_reproductivo', 'Estado reproductivo', 'select', NULL, 'reproduccion', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (12, 'vacunacion', 'Vacunación', 'select', NULL, 'salud', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (13, 'desparasitacion', 'Última desparasitación', 'date', NULL, 'salud', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (14, 'estado_sanitario', 'Estado sanitario', 'select', NULL, 'salud', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (15, 'enfermedades', 'Enfermedades conocidas', 'text', NULL, 'salud', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (16, 'tratamientos', 'Tratamientos', 'text', NULL, 'salud', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (17, 'raza_bovino', 'Raza', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (18, 'sexo', 'Sexo', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (19, 'edad_meses', 'Edad', 'number', 'meses', 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (20, 'proposito_bovino', 'Propósito', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (21, 'arete', 'Identificación / arete', 'text', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (22, 'condicion_corporal', 'Condición corporal', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (23, 'partos', 'Número de partos', 'number', NULL, 'reproduccion', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (24, 'leche_litros', 'Producción de leche', 'number', 'L/día', 'produccion', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (25, 'raza_ovino', 'Raza', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (26, 'proposito_ovino', 'Propósito', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (27, 'raza_caprino', 'Raza', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (28, 'proposito_caprino', 'Propósito', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (29, 'raza_genetica_porcino', 'Raza / genética', 'text', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (30, 'proposito_porcino', 'Propósito', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (31, 'alimentacion', 'Alimentación', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (32, 'tipo_colmena', 'Tipo de colmena', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (33, 'alzas', 'Número de alzas', 'number', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (35, 'bastidores_cria', 'Bastidores con cría', 'number', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (36, 'genetica', 'Genética / línea', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (37, 'fuerza', 'Fuerza de colonia', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (38, 'edad_reina', 'Edad de la reina', 'number', 'meses', 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (39, 'ultima_inspeccion', 'Última inspección', 'date', NULL, 'salud', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (40, 'estado_sanitario_colmena', 'Estado sanitario', 'select', NULL, 'salud', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (34, 'bastidores', 'Bastidores', 'number', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (41, 'reina_fecundada', 'Reina fecundada', 'boolean', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (42, 'fecundada', 'Fecundada', 'boolean', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (43, 'marcada', 'Marcada', 'boolean', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (44, 'tipo_miel', 'Tipo de miel', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (45, 'origen_floral', 'Origen floral', 'text', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (46, 'fecha_cosecha', 'Fecha de cosecha', 'date', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (47, 'lote', 'Lote', 'text', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (48, 'extraccion', 'Método de extracción', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (49, 'presentacion_miel', 'Presentación', 'select', NULL, 'comercial', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (50, 'disponible_kg', 'Disponibilidad', 'number', 'kg', 'comercial', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (51, 'pedido_minimo', 'Pedido mínimo', 'number', 'kg', 'comercial', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (52, 'variedad', 'Variedad', 'text', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (53, 'metodo_cultivo', 'Método de cultivo', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (54, 'sistema', 'Sistema', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (55, 'calidad', 'Calidad', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (56, 'madurez', 'Madurez', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (57, 'presentacion_cosecha', 'Presentación', 'select', NULL, 'comercial', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (58, 'especie', 'Especie', 'text', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (59, 'altura_cm', 'Altura', 'number', 'cm', 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (60, 'contenedor', 'Contenedor', 'select', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (61, 'injertada', 'Injertada', 'boolean', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.atributos VALUES (62, 'patron', 'Patrón', 'text', NULL, 'general', '2026-10-07 07:04:46', '2026-10-07 07:04:46');


--
-- Data for Name: categorias; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.categorias VALUES (1, NULL, 'Animales', 'animales', 'animales', 'cow', 1, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.categorias VALUES (2, NULL, 'Apicultura', 'apicultura', 'apicultura', 'bee', 2, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.categorias VALUES (3, NULL, 'Agricultura', 'agricultura', 'agricultura', 'chili', 3, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');


--
-- Data for Name: opciones_atributo; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.opciones_atributo VALUES (1, 1, 'Cuarto de Milla', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (2, 1, 'Azteca', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (3, 1, 'Pura Sangre', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (4, 1, 'Criollo', 3, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (5, 1, 'Appaloosa', 4, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (6, 1, 'Otra', 5, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (7, 2, 'Macho', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (8, 2, 'Hembra', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (9, 2, 'Macho castrado', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (10, 7, 'Rienda', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (11, 7, 'Charrería', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (12, 7, 'Trabajo de campo', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (13, 7, 'Paseo', 3, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (14, 7, 'Carreras', 4, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (15, 7, 'Salto', 5, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (16, 8, 'Sin domar', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (17, 8, 'Básico', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (18, 8, 'Intermedio', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (19, 8, 'Avanzado', 3, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (20, 9, 'Dócil', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (21, 9, 'Moderado', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (22, 9, 'Enérgico', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (23, 11, 'No aplica', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (24, 11, 'Vacía', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (25, 11, 'Gestante', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (26, 11, 'Lactando', 3, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (27, 11, 'Semental activo', 4, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (28, 12, 'Al corriente', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (29, 12, 'Parcial', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (30, 12, 'Sin registro', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (31, 14, 'Sano', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (32, 14, 'En tratamiento', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (33, 14, 'En observación', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (34, 17, 'Brahman', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (35, 17, 'Suizo', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (36, 17, 'Brahman x Suizo', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (37, 17, 'Nelore', 3, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (38, 17, 'Gyr', 4, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (39, 17, 'Holstein', 5, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (40, 17, 'Charolais', 6, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (41, 17, 'Angus', 7, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (42, 17, 'Otra', 8, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (43, 18, 'Macho', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (44, 18, 'Hembra', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (45, 20, 'Engorda', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (46, 20, 'Cría', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (47, 20, 'Leche', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (48, 20, 'Doble propósito', 3, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (49, 20, 'Pie de cría', 4, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (50, 22, '1', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (51, 22, '2', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (52, 22, '3', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (53, 22, '4', 3, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (54, 22, '5', 4, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (55, 25, 'Pelibuey', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (56, 25, 'Katahdin', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (57, 25, 'Dorper', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (58, 25, 'Blackbelly', 3, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (59, 25, 'Cruza', 4, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (60, 26, 'Engorda', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (61, 26, 'Pie de cría', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (62, 26, 'Reproductor', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (63, 27, 'Saanen', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (64, 27, 'Alpina', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (65, 27, 'Nubia', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (66, 27, 'Boer', 3, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (67, 27, 'Criolla', 4, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (68, 28, 'Leche', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (69, 28, 'Carne', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (70, 28, 'Pie de cría', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (71, 30, 'Engorda', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (72, 30, 'Pie de cría', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (73, 30, 'Semental', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (74, 31, 'Alimento balanceado', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (75, 31, 'Mixta', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (76, 31, 'Traspatio', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (77, 32, 'Langstroth', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (78, 32, 'Jumbo', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (79, 32, 'Dadant', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (80, 36, 'Italiana', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (81, 36, 'Carniola', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (82, 36, 'Africanizada', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (83, 36, 'Local', 3, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (84, 37, 'Débil', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (85, 37, 'Media', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (86, 37, 'Fuerte', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (87, 40, 'Sana', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (88, 40, 'En tratamiento', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (89, 40, 'En observación', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (90, 44, 'Multifloral', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (91, 44, 'Monofloral', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (92, 44, 'Cremosa', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (93, 48, 'Centrífuga', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (94, 48, 'Prensado', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (95, 49, 'Granel (tambo)', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (96, 49, 'Cubeta', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (97, 49, 'Frasco 1 kg', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (98, 49, 'Frasco 500 g', 3, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (99, 53, 'Convencional', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (100, 53, 'Orgánico (sin certificar)', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (101, 53, 'Orgánico certificado', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (102, 54, 'Campo abierto', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (103, 54, 'Invernadero', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (104, 54, 'Malla sombra', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (105, 55, 'Primera', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (106, 55, 'Segunda', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (107, 55, 'Tercera', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (108, 56, 'Verde', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (109, 56, 'Pintón', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (110, 56, 'Maduro', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (111, 57, 'Granel', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (112, 57, 'Caja', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (113, 57, 'Arpilla', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (114, 57, 'Tonelada', 3, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (115, 60, 'Bolsa', 0, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (116, 60, 'Maceta', 1, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (117, 60, 'Charola', 2, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.opciones_atributo VALUES (118, 60, 'Raíz desnuda', 3, '2026-10-07 07:04:46', '2026-10-07 07:04:46');


--
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.permissions VALUES (1, 'manage own listings', 'web', '2026-10-07 07:04:45', '2026-10-07 07:04:45');
INSERT INTO public.permissions VALUES (2, 'moderate listings', 'web', '2026-10-07 07:04:45', '2026-10-07 07:04:45');
INSERT INTO public.permissions VALUES (3, 'moderate documents', 'web', '2026-10-07 07:04:45', '2026-10-07 07:04:45');
INSERT INTO public.permissions VALUES (4, 'resolve reports', 'web', '2026-10-07 07:04:45', '2026-10-07 07:04:45');
INSERT INTO public.permissions VALUES (5, 'manage compliance rules', 'web', '2026-10-07 07:04:45', '2026-10-07 07:04:45');
INSERT INTO public.permissions VALUES (6, 'manage catalog', 'web', '2026-10-07 07:04:45', '2026-10-07 07:04:45');
INSERT INTO public.permissions VALUES (7, 'manage users', 'web', '2026-10-07 07:04:45', '2026-10-07 07:04:45');
INSERT INTO public.permissions VALUES (8, 'view audit logs', 'web', '2026-10-07 07:04:45', '2026-10-07 07:04:45');


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.roles VALUES (1, 'vendedor', 'web', '2026-10-07 07:04:45', '2026-10-07 07:04:45');
INSERT INTO public.roles VALUES (2, 'moderador', 'web', '2026-10-07 07:04:45', '2026-10-07 07:04:45');
INSERT INTO public.roles VALUES (3, 'administrador', 'web', '2026-10-07 07:04:45', '2026-10-07 07:04:45');


--
-- Data for Name: role_has_permissions; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.role_has_permissions VALUES (1, 1);
INSERT INTO public.role_has_permissions VALUES (1, 2);
INSERT INTO public.role_has_permissions VALUES (2, 2);
INSERT INTO public.role_has_permissions VALUES (3, 2);
INSERT INTO public.role_has_permissions VALUES (4, 2);
INSERT INTO public.role_has_permissions VALUES (1, 3);
INSERT INTO public.role_has_permissions VALUES (2, 3);
INSERT INTO public.role_has_permissions VALUES (3, 3);
INSERT INTO public.role_has_permissions VALUES (4, 3);
INSERT INTO public.role_has_permissions VALUES (5, 3);
INSERT INTO public.role_has_permissions VALUES (6, 3);
INSERT INTO public.role_has_permissions VALUES (7, 3);
INSERT INTO public.role_has_permissions VALUES (8, 3);


--
-- Data for Name: tipos_producto; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.tipos_producto VALUES (1, 1, 'Caballos', 'equinos', 'horse', 45, 0, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipos_producto VALUES (2, 1, 'Bovinos', 'bovinos', 'cow', 45, 0, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipos_producto VALUES (3, 1, 'Ovinos', 'ovinos', 'sheep', 45, 0, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipos_producto VALUES (4, 1, 'Caprinos', 'caprinos', 'goat', 45, 0, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipos_producto VALUES (5, 1, 'Porcinos', 'porcinos', 'pig', 45, 0, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipos_producto VALUES (6, 2, 'Colmenas', 'colmenas', 'hive', 30, 0, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipos_producto VALUES (7, 2, 'Núcleos', 'nucleos', 'bee', 30, 0, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipos_producto VALUES (8, 2, 'Abejas reina', 'reinas', 'crown', 30, 0, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipos_producto VALUES (9, 2, 'Miel', 'miel', 'honey', 60, 0, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipos_producto VALUES (10, 3, 'Chiles', 'chiles', 'chili', 20, 0, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipos_producto VALUES (11, 3, 'Frutas', 'frutas', 'fruit', 20, 0, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipos_producto VALUES (12, 3, 'Hortalizas', 'hortalizas', 'leaf', 20, 0, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipos_producto VALUES (13, 3, 'Plantas', 'plantas', 'sprout', 60, 0, true, '2026-10-07 07:04:46', '2026-10-07 07:04:46');


--
-- Data for Name: tipo_producto_atributos; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.tipo_producto_atributos VALUES (1, 1, 1, true, true, 0, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (2, 1, 2, true, true, 1, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (3, 1, 3, true, true, 2, 0.00, 30.00, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (4, 1, 4, false, false, 3, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (5, 1, 5, false, false, 4, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (6, 1, 6, false, false, 5, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (7, 1, 7, false, true, 6, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (8, 1, 8, false, false, 7, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (9, 1, 9, false, false, 8, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (10, 1, 10, false, false, 9, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (11, 1, 11, false, false, 10, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (12, 1, 12, true, false, 11, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (13, 1, 13, false, false, 12, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (14, 1, 14, true, false, 13, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (15, 1, 15, false, false, 14, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (16, 1, 16, false, false, 15, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (17, 2, 17, true, true, 0, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (18, 2, 18, true, true, 1, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (19, 2, 19, true, true, 2, 0.00, 120.00, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (20, 2, 4, true, true, 3, 0.00, 900.00, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (21, 2, 20, true, true, 4, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (22, 2, 21, false, false, 5, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (23, 2, 22, false, false, 6, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (24, 2, 11, false, false, 7, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (25, 2, 23, false, false, 8, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (26, 2, 24, false, false, 9, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (27, 2, 12, true, false, 10, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (28, 2, 13, false, false, 11, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (29, 2, 14, true, false, 12, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (30, 2, 15, false, false, 13, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (31, 2, 16, false, false, 14, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (32, 3, 25, true, true, 0, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (33, 3, 18, true, true, 1, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (34, 3, 19, true, true, 2, 0.00, 120.00, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (35, 3, 4, true, true, 3, 0.00, 900.00, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (36, 3, 26, true, true, 4, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (37, 3, 22, false, false, 5, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (38, 3, 11, false, false, 6, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (39, 3, 23, false, false, 7, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (40, 3, 12, true, false, 8, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (41, 3, 13, false, false, 9, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (42, 3, 14, true, false, 10, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (43, 3, 15, false, false, 11, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (44, 3, 16, false, false, 12, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (45, 4, 27, true, true, 0, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (46, 4, 18, true, true, 1, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (47, 4, 19, true, true, 2, 0.00, 120.00, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (48, 4, 4, true, true, 3, 0.00, 900.00, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (49, 4, 28, true, true, 4, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (50, 4, 24, false, false, 5, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (51, 4, 11, false, false, 6, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (52, 4, 23, false, false, 7, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (53, 4, 12, true, false, 8, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (54, 4, 13, false, false, 9, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (55, 4, 14, true, false, 10, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (56, 4, 15, false, false, 11, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (57, 4, 16, false, false, 12, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (58, 5, 29, true, false, 0, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (59, 5, 18, true, true, 1, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (60, 5, 19, true, true, 2, 0.00, 120.00, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (61, 5, 4, true, true, 3, 0.00, 900.00, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (62, 5, 30, true, true, 4, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (63, 5, 31, false, false, 5, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (64, 5, 12, true, false, 6, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (65, 5, 13, false, false, 7, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (66, 5, 14, true, false, 8, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (67, 5, 15, false, false, 9, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (68, 5, 16, false, false, 10, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (69, 6, 32, true, true, 0, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (70, 6, 33, true, false, 1, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (71, 6, 34, true, false, 2, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (72, 6, 35, false, false, 3, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (73, 6, 36, false, true, 4, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (74, 6, 37, true, false, 5, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (75, 6, 38, false, false, 6, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (76, 6, 39, false, false, 7, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (77, 6, 40, true, false, 8, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (78, 7, 34, true, false, 0, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (79, 7, 35, true, false, 1, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (80, 7, 41, true, false, 2, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (81, 7, 36, false, true, 3, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (82, 8, 36, true, true, 0, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (83, 8, 42, true, false, 1, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (84, 8, 43, false, false, 2, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (85, 9, 44, true, true, 0, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (86, 9, 45, true, false, 1, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (87, 9, 46, true, false, 2, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (88, 9, 47, false, false, 3, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (89, 9, 48, false, false, 4, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (90, 9, 49, true, false, 5, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (91, 9, 50, true, true, 6, 0.00, 20000.00, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (92, 9, 51, false, false, 7, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (93, 10, 52, true, true, 0, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (94, 10, 53, true, true, 1, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (95, 10, 54, false, false, 2, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (96, 10, 46, true, false, 3, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (97, 10, 55, true, true, 4, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (98, 10, 56, false, false, 5, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (99, 10, 57, false, false, 6, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (100, 10, 50, true, true, 7, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (101, 10, 51, false, false, 8, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (102, 11, 52, true, true, 0, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (103, 11, 53, true, true, 1, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (104, 11, 54, false, false, 2, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (105, 11, 46, true, false, 3, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (106, 11, 55, true, true, 4, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (107, 11, 56, false, false, 5, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (108, 11, 57, false, false, 6, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (109, 11, 50, true, true, 7, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (110, 11, 51, false, false, 8, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (111, 12, 52, true, true, 0, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (112, 12, 53, true, true, 1, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (113, 12, 54, false, false, 2, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (114, 12, 46, true, false, 3, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (115, 12, 55, true, true, 4, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (116, 12, 56, false, false, 5, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (117, 12, 57, false, false, 6, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (118, 12, 50, true, true, 7, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (119, 12, 51, false, false, 8, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (120, 13, 58, true, true, 0, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (121, 13, 52, true, false, 1, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (122, 13, 19, false, false, 2, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (123, 13, 59, true, false, 3, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (124, 13, 60, true, false, 4, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (125, 13, 61, false, true, 5, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');
INSERT INTO public.tipo_producto_atributos VALUES (126, 13, 62, false, false, 6, NULL, NULL, '2026-10-07 07:04:46', '2026-10-07 07:04:46');


--
-- Name: atributos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.atributos_id_seq', 62, true);


--
-- Name: categorias_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.categorias_id_seq', 3, true);


--
-- Name: opciones_atributo_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.opciones_atributo_id_seq', 118, true);


--
-- Name: permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.permissions_id_seq', 8, true);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.roles_id_seq', 3, true);


--
-- Name: tipo_producto_atributos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.tipo_producto_atributos_id_seq', 126, true);


--
-- Name: tipos_producto_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.tipos_producto_id_seq', 13, true);


--
-- PostgreSQL database dump complete
--

\unrestrict UjVbPfHYlpBk45UASkXTsfodTUNDC9Nc6ZuuCCovD5pBl8mGiWJZd8PdHACmf3z

