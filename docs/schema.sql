--
-- PostgreSQL database dump
--

\restrict 6VGZj91gQI0rrzSPXEeAU3YlzsUUfos5FucyPLAeAOTv7Eho1hwgJTteLEPtU6v

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
-- Name: fuzzystrmatch; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS fuzzystrmatch WITH SCHEMA public;


--
-- Name: EXTENSION fuzzystrmatch; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION fuzzystrmatch IS 'determine similarities and distance between strings';


--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: postgis; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis WITH SCHEMA public;


--
-- Name: EXTENSION postgis; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION postgis IS 'PostGIS geometry and geography spatial types and functions';


--
-- Name: postgis_tiger_geocoder; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis_tiger_geocoder WITH SCHEMA tiger;


--
-- Name: EXTENSION postgis_tiger_geocoder; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION postgis_tiger_geocoder IS 'PostGIS tiger geocoder and reverse geocoder';


--
-- Name: postgis_topology; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis_topology WITH SCHEMA topology;


--
-- Name: EXTENSION postgis_topology; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION postgis_topology IS 'PostGIS topology spatial types and functions';


--
-- Name: unaccent; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS unaccent WITH SCHEMA public;


--
-- Name: EXTENSION unaccent; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION unaccent IS 'text search dictionary that removes accents';


--
-- Name: immutable_unaccent(text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.immutable_unaccent(text) RETURNS text
    LANGUAGE sql IMMUTABLE STRICT PARALLEL SAFE
    AS $_$
      SELECT public.unaccent('public.unaccent'::regdictionary, $1)
    $_$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: atributos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.atributos (
    id bigint NOT NULL,
    clave character varying(100) NOT NULL,
    etiqueta character varying(150) NOT NULL,
    tipo_dato character varying(255) NOT NULL,
    unidad character varying(30),
    grupo character varying(255) DEFAULT 'general'::character varying NOT NULL,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone,
    CONSTRAINT atributos_grupo_check CHECK (((grupo)::text = ANY ((ARRAY['general'::character varying, 'salud'::character varying, 'reproduccion'::character varying, 'produccion'::character varying, 'comercial'::character varying])::text[]))),
    CONSTRAINT atributos_tipo_dato_check CHECK (((tipo_dato)::text = ANY ((ARRAY['text'::character varying, 'number'::character varying, 'select'::character varying, 'boolean'::character varying, 'date'::character varying])::text[])))
);


--
-- Name: atributos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.atributos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: atributos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.atributos_id_seq OWNED BY public.atributos.id;


--
-- Name: bitacora_auditoria; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bitacora_auditoria (
    id bigint NOT NULL,
    usuario_id bigint,
    accion character varying(100) NOT NULL,
    auditable_tipo character varying(60),
    auditable_id bigint,
    cambios jsonb,
    direccion_ip character varying(45),
    agente_usuario character varying(255),
    creado_en timestamp(0) without time zone
);


--
-- Name: bitacora_auditoria_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bitacora_auditoria_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bitacora_auditoria_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bitacora_auditoria_id_seq OWNED BY public.bitacora_auditoria.id;


--
-- Name: busquedas_guardadas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.busquedas_guardadas (
    id bigint NOT NULL,
    usuario_id bigint NOT NULL,
    nombre character varying(150) NOT NULL,
    consulta jsonb DEFAULT '{}'::jsonb NOT NULL,
    avisar_coincidencia boolean DEFAULT true NOT NULL,
    avisar_cambio_precio boolean DEFAULT false NOT NULL,
    ultimo_aviso_en timestamp(0) without time zone,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone
);


--
-- Name: busquedas_guardadas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.busquedas_guardadas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: busquedas_guardadas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.busquedas_guardadas_id_seq OWNED BY public.busquedas_guardadas.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: categorias; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categorias (
    id bigint NOT NULL,
    categoria_padre_id bigint,
    nombre character varying(100) NOT NULL,
    slug character varying(100) NOT NULL,
    clave_color character varying(50) NOT NULL,
    icono character varying(50),
    orden smallint DEFAULT '0'::smallint NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone
);


--
-- Name: categorias_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categorias_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categorias_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categorias_id_seq OWNED BY public.categorias.id;


--
-- Name: codigos_verificacion_contacto; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.codigos_verificacion_contacto (
    id bigint NOT NULL,
    usuario_id bigint NOT NULL,
    canal character varying(10) NOT NULL,
    destino character varying(180) NOT NULL,
    codigo_hash character varying(255) NOT NULL,
    intentos smallint DEFAULT '0'::smallint NOT NULL,
    expira_en timestamp(0) without time zone NOT NULL,
    creado_en timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: codigos_verificacion_contacto_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.codigos_verificacion_contacto_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: codigos_verificacion_contacto_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.codigos_verificacion_contacto_id_seq OWNED BY public.codigos_verificacion_contacto.id;


--
-- Name: conversaciones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.conversaciones (
    id bigint NOT NULL,
    publicacion_id bigint NOT NULL,
    comprador_id bigint NOT NULL,
    vendedor_id bigint NOT NULL,
    ultimo_mensaje_en timestamp(0) without time zone,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone
);


--
-- Name: conversaciones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.conversaciones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: conversaciones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.conversaciones_id_seq OWNED BY public.conversaciones.id;


--
-- Name: documentos_publicacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.documentos_publicacion (
    id bigint NOT NULL,
    publicacion_id bigint NOT NULL,
    nombre character varying(180) NOT NULL,
    ruta_almacenamiento character varying(255) NOT NULL,
    estatus character varying(255) DEFAULT 'pendiente'::character varying NOT NULL,
    nota character varying(255),
    revisado_por bigint,
    revisado_en timestamp(0) without time zone,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone,
    CONSTRAINT documentos_publicacion_estatus_check CHECK (((estatus)::text = ANY ((ARRAY['pendiente'::character varying, 'verified'::character varying, 'rechazada'::character varying, 'vencida'::character varying, 'not_applicable'::character varying])::text[])))
);


--
-- Name: documentos_publicacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.documentos_publicacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: documentos_publicacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.documentos_publicacion_id_seq OWNED BY public.documentos_publicacion.id;


--
-- Name: documentos_verificacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.documentos_verificacion (
    id bigint NOT NULL,
    solicitud_id bigint NOT NULL,
    tipo character varying(30) NOT NULL,
    ruta_almacenamiento character varying(255) NOT NULL,
    nombre_original character varying(255),
    tipo_mime character varying(100) NOT NULL,
    tamano integer NOT NULL,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone,
    disco character varying(40)
);


--
-- Name: documentos_verificacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.documentos_verificacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: documentos_verificacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.documentos_verificacion_id_seq OWNED BY public.documentos_verificacion.id;


--
-- Name: eventos_operacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.eventos_operacion (
    id bigint NOT NULL,
    operacion_id bigint NOT NULL,
    estatus_anterior character varying(30),
    estatus_nuevo character varying(30) NOT NULL,
    actor_id bigint,
    nota character varying(255),
    creado_en timestamp(0) without time zone
);


--
-- Name: eventos_operacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.eventos_operacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: eventos_operacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.eventos_operacion_id_seq OWNED BY public.eventos_operacion.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: favoritos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.favoritos (
    id bigint NOT NULL,
    usuario_id bigint NOT NULL,
    publicacion_id bigint NOT NULL,
    creado_en timestamp(0) without time zone
);


--
-- Name: favoritos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.favoritos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: favoritos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.favoritos_id_seq OWNED BY public.favoritos.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: medios_publicacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.medios_publicacion (
    id bigint NOT NULL,
    publicacion_id bigint NOT NULL,
    tipo character varying(255) DEFAULT 'foto'::character varying NOT NULL,
    ruta_almacenamiento character varying(255) NOT NULL,
    posicion smallint DEFAULT '0'::smallint NOT NULL,
    ancho smallint,
    alto smallint,
    duracion_segundos smallint,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone,
    CONSTRAINT medios_publicacion_tipo_check CHECK (((tipo)::text = ANY ((ARRAY['foto'::character varying, 'video'::character varying])::text[])))
);


--
-- Name: medios_publicacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.medios_publicacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: medios_publicacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.medios_publicacion_id_seq OWNED BY public.medios_publicacion.id;


--
-- Name: mensajes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mensajes (
    id bigint NOT NULL,
    conversacion_id bigint NOT NULL,
    remitente_id bigint NOT NULL,
    oferta_id bigint,
    cuerpo text,
    leido_en timestamp(0) without time zone,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone,
    CONSTRAINT mensajes_cuerpo_u_oferta_chk CHECK (((cuerpo IS NOT NULL) OR (oferta_id IS NOT NULL)))
);


--
-- Name: mensajes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mensajes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mensajes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mensajes_id_seq OWNED BY public.mensajes.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_permissions (
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


--
-- Name: notificaciones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notificaciones (
    id uuid NOT NULL,
    tipo character varying(255) NOT NULL,
    notificable_tipo character varying(255) NOT NULL,
    notificable_id bigint NOT NULL,
    datos jsonb NOT NULL,
    leido_en timestamp(0) without time zone,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone
);


--
-- Name: ofertas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ofertas (
    id bigint NOT NULL,
    conversacion_id bigint NOT NULL,
    remitente_id bigint NOT NULL,
    monto numeric(14,2) NOT NULL,
    cantidad numeric(12,2) DEFAULT '1'::numeric NOT NULL,
    estatus character varying(255) DEFAULT 'enviada'::character varying NOT NULL,
    vence_en timestamp(0) without time zone,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone,
    CONSTRAINT ofertas_estatus_check CHECK (((estatus)::text = ANY ((ARRAY['enviada'::character varying, 'aceptada'::character varying, 'rechazada'::character varying, 'contraoferta'::character varying, 'cancelled'::character varying, 'vencida'::character varying])::text[])))
);


--
-- Name: ofertas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ofertas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ofertas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ofertas_id_seq OWNED BY public.ofertas.id;


--
-- Name: opciones_atributo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.opciones_atributo (
    id bigint NOT NULL,
    atributo_id bigint NOT NULL,
    valor character varying(150) NOT NULL,
    orden smallint DEFAULT '0'::smallint NOT NULL,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone
);


--
-- Name: opciones_atributo_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.opciones_atributo_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: opciones_atributo_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.opciones_atributo_id_seq OWNED BY public.opciones_atributo.id;


--
-- Name: operaciones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.operaciones (
    id bigint NOT NULL,
    oferta_id bigint NOT NULL,
    publicacion_id bigint NOT NULL,
    comprador_id bigint NOT NULL,
    vendedor_id bigint NOT NULL,
    monto numeric(14,2) NOT NULL,
    cantidad numeric(12,2) NOT NULL,
    estatus character varying(255) DEFAULT 'oferta_aceptada'::character varying NOT NULL,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone,
    CONSTRAINT operaciones_estatus_check CHECK (((estatus)::text = ANY ((ARRAY['oferta_aceptada'::character varying, 'pendiente_pago'::character varying, 'pagado'::character varying, 'preparando_entrega'::character varying, 'en_transito'::character varying, 'entregado'::character varying, 'confirmado'::character varying, 'completado'::character varying, 'cancelado'::character varying, 'disputa'::character varying])::text[])))
);


--
-- Name: operaciones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.operaciones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: operaciones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.operaciones_id_seq OWNED BY public.operaciones.id;


--
-- Name: perfiles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.perfiles (
    usuario_id bigint NOT NULL,
    ruta_avatar character varying(255),
    biografia text,
    estado character varying(100),
    municipio character varying(100),
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone
);


--
-- Name: perfiles_vendedor; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.perfiles_vendedor (
    usuario_id bigint NOT NULL,
    nombre_negocio character varying(255),
    verificado boolean DEFAULT false NOT NULL,
    verificado_en timestamp(0) without time zone,
    operaciones_completadas integer DEFAULT 0 NOT NULL,
    operaciones_canceladas integer DEFAULT 0 NOT NULL,
    calificacion_exactitud numeric(3,2),
    calificacion_cumplimiento numeric(3,2),
    calificacion_comunicacion numeric(3,2),
    minutos_respuesta_promedio integer,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone
);


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: predios; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.predios (
    id bigint NOT NULL,
    usuario_id bigint NOT NULL,
    nombre character varying(255) NOT NULL,
    estado character varying(100) NOT NULL,
    municipio character varying(100) NOT NULL,
    codigo_postal character varying(10),
    ubicacion_exacta public.geography(Point,4326),
    ubicacion_aproximada public.geography(Point,4326),
    es_predeterminado boolean DEFAULT false NOT NULL,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone
);


--
-- Name: predios_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.predios_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: predios_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.predios_id_seq OWNED BY public.predios.id;


--
-- Name: publicaciones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.publicaciones (
    id bigint NOT NULL,
    usuario_id bigint NOT NULL,
    tipo_producto_id bigint NOT NULL,
    predio_id bigint,
    titulo character varying(180) NOT NULL,
    slug character varying(220) NOT NULL,
    descripcion text,
    precio numeric(14,2),
    tipo_precio character varying(255) DEFAULT 'fixed'::character varying NOT NULL,
    moneda character(3) DEFAULT 'MXN'::bpchar NOT NULL,
    cantidad numeric(12,2) DEFAULT '1'::numeric NOT NULL,
    unidad character varying(30) DEFAULT 'unidad'::character varying NOT NULL,
    modalidad_venta character varying(255) DEFAULT 'individual'::character varying NOT NULL,
    negociable boolean DEFAULT false NOT NULL,
    estatus character varying(255) DEFAULT 'borrador'::character varying NOT NULL,
    estatus_moderacion character varying(255) DEFAULT 'pendiente'::character varying NOT NULL,
    atributos_cache jsonb DEFAULT '{}'::jsonb NOT NULL,
    publicado_en timestamp(0) without time zone,
    vence_en timestamp(0) without time zone,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone,
    eliminado_en timestamp(0) without time zone,
    motivo_moderacion character varying(300),
    CONSTRAINT publicaciones_estatus_check CHECK (((estatus)::text = ANY ((ARRAY['borrador'::character varying, 'en_revision'::character varying, 'publicada'::character varying, 'rechazada'::character varying, 'suspended'::character varying, 'sold'::character varying, 'vencida'::character varying, 'archivada'::character varying])::text[]))),
    CONSTRAINT publicaciones_estatus_moderacion_check CHECK (((estatus_moderacion)::text = ANY ((ARRAY['pendiente'::character varying, 'aprobada'::character varying, 'rechazada'::character varying])::text[]))),
    CONSTRAINT publicaciones_modalidad_venta_check CHECK (((modalidad_venta)::text = ANY ((ARRAY['individual'::character varying, 'lot'::character varying])::text[]))),
    CONSTRAINT publicaciones_moneda_mxn_chk CHECK ((moneda = 'MXN'::bpchar)),
    CONSTRAINT publicaciones_tipo_precio_check CHECK (((tipo_precio)::text = ANY ((ARRAY['fixed'::character varying, 'per_unit'::character varying, 'per_kg'::character varying, 'per_animal'::character varying, 'per_lot'::character varying, 'quote'::character varying])::text[])))
);


--
-- Name: publicaciones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.publicaciones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: publicaciones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.publicaciones_id_seq OWNED BY public.publicaciones.id;


--
-- Name: reglas_cumplimiento; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reglas_cumplimiento (
    id bigint NOT NULL,
    tipo_producto_id bigint,
    categoria_id bigint,
    titulo character varying(180) NOT NULL,
    descripcion text,
    documento_sugerido boolean DEFAULT false NOT NULL,
    documento_requerido boolean DEFAULT false NOT NULL,
    nombre_fuente character varying(120) NOT NULL,
    url_fuente character varying(255),
    vigente_desde date,
    vigente_hasta date,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone,
    CONSTRAINT reglas_cumplimiento_alcance_chk CHECK (((tipo_producto_id IS NOT NULL) OR (categoria_id IS NOT NULL)))
);


--
-- Name: reglas_cumplimiento_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reglas_cumplimiento_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reglas_cumplimiento_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reglas_cumplimiento_id_seq OWNED BY public.reglas_cumplimiento.id;


--
-- Name: reportes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reportes (
    id bigint NOT NULL,
    reportante_id bigint NOT NULL,
    reportable_tipo character varying(60) NOT NULL,
    reportable_id bigint NOT NULL,
    motivo character varying(255) NOT NULL,
    descripcion character varying(500),
    estatus character varying(255) DEFAULT 'abierto'::character varying NOT NULL,
    resuelto_por bigint,
    resuelto_en timestamp(0) without time zone,
    nota_resolucion character varying(500),
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone,
    CONSTRAINT reportes_estatus_check CHECK (((estatus)::text = ANY ((ARRAY['abierto'::character varying, 'investigating'::character varying, 'resuelto'::character varying, 'dismissed'::character varying])::text[]))),
    CONSTRAINT reportes_motivo_check CHECK (((motivo)::text = ANY ((ARRAY['fraude'::character varying, 'informacion_falsa'::character varying, 'producto_inexistente'::character varying, 'documentacion_sospechosa'::character varying, 'publicacion_duplicada'::character varying, 'conducta_inapropiada'::character varying, 'producto_no_permitido'::character varying, 'otro'::character varying])::text[])))
);


--
-- Name: reportes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reportes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reportes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reportes_id_seq OWNED BY public.reportes.id;


--
-- Name: resenas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.resenas (
    id bigint NOT NULL,
    operacion_id bigint NOT NULL,
    autor_id bigint NOT NULL,
    evaluado_id bigint NOT NULL,
    exactitud smallint,
    cumplimiento smallint,
    comunicacion smallint,
    pago smallint,
    recepcion smallint,
    comentario character varying(500),
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone,
    CONSTRAINT resenas_calificaciones_rango_chk CHECK ((((exactitud IS NULL) OR ((exactitud >= 1) AND (exactitud <= 5))) AND ((cumplimiento IS NULL) OR ((cumplimiento >= 1) AND (cumplimiento <= 5))) AND ((comunicacion IS NULL) OR ((comunicacion >= 1) AND (comunicacion <= 5))) AND ((pago IS NULL) OR ((pago >= 1) AND (pago <= 5))) AND ((recepcion IS NULL) OR ((recepcion >= 1) AND (recepcion <= 5)))))
);


--
-- Name: resenas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.resenas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: resenas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.resenas_id_seq OWNED BY public.resenas.id;


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: solicitudes_verificacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.solicitudes_verificacion (
    id bigint NOT NULL,
    usuario_id bigint NOT NULL,
    estatus character varying(20) DEFAULT 'pendiente'::character varying NOT NULL,
    nombre_negocio character varying(150),
    motivo_rechazo text,
    revisado_por bigint,
    revisado_en timestamp(0) without time zone,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone
);


--
-- Name: solicitudes_verificacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.solicitudes_verificacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: solicitudes_verificacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.solicitudes_verificacion_id_seq OWNED BY public.solicitudes_verificacion.id;


--
-- Name: tipo_producto_atributos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tipo_producto_atributos (
    id bigint NOT NULL,
    tipo_producto_id bigint NOT NULL,
    atributo_id bigint NOT NULL,
    es_obligatorio boolean DEFAULT false NOT NULL,
    es_filtrable boolean DEFAULT false NOT NULL,
    orden smallint DEFAULT '0'::smallint NOT NULL,
    valor_minimo numeric(12,2),
    valor_maximo numeric(12,2),
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone
);


--
-- Name: tipo_producto_atributos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tipo_producto_atributos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tipo_producto_atributos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tipo_producto_atributos_id_seq OWNED BY public.tipo_producto_atributos.id;


--
-- Name: tipos_producto; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tipos_producto (
    id bigint NOT NULL,
    categoria_id bigint NOT NULL,
    nombre character varying(100) NOT NULL,
    slug character varying(100) NOT NULL,
    icono character varying(50),
    dias_vigencia_predeterminados smallint DEFAULT '60'::smallint NOT NULL,
    orden smallint DEFAULT '0'::smallint NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone
);


--
-- Name: tipos_producto_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tipos_producto_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tipos_producto_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tipos_producto_id_seq OWNED BY public.tipos_producto.id;


--
-- Name: tokens_dispositivo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tokens_dispositivo (
    id bigint NOT NULL,
    usuario_id bigint NOT NULL,
    token text NOT NULL,
    plataforma character varying(20) DEFAULT 'android'::character varying NOT NULL,
    ultimo_uso_en timestamp(0) without time zone,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone
);


--
-- Name: tokens_dispositivo_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tokens_dispositivo_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tokens_dispositivo_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tokens_dispositivo_id_seq OWNED BY public.tokens_dispositivo.id;


--
-- Name: ubicaciones_publicacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ubicaciones_publicacion (
    publicacion_id bigint NOT NULL,
    predio_id bigint,
    estado character varying(100) NOT NULL,
    municipio character varying(100) NOT NULL,
    codigo_postal character varying(10),
    ubicacion_exacta public.geography(Point,4326) NOT NULL,
    ubicacion_aproximada public.geography(Point,4326) NOT NULL,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone
);


--
-- Name: usuarios; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usuarios (
    id bigint NOT NULL,
    nombre character varying(255) NOT NULL,
    correo character varying(255) NOT NULL,
    telefono character varying(20),
    correo_verificado_en timestamp(0) without time zone,
    contrasena character varying(255) NOT NULL,
    secreto_dos_factores text,
    codigos_recuperacion_dos_factores text,
    dos_factores_confirmado_en timestamp(0) without time zone,
    token_recordar character varying(100),
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone,
    eliminado_en timestamp(0) without time zone,
    dos_factores_ultimo_paso bigint,
    apellidos character varying(150),
    telefono_verificado_en timestamp(0) without time zone
);


--
-- Name: usuarios_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usuarios_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usuarios_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.usuarios_id_seq OWNED BY public.usuarios.id;


--
-- Name: valores_atributo_publicacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.valores_atributo_publicacion (
    id bigint NOT NULL,
    publicacion_id bigint NOT NULL,
    atributo_id bigint NOT NULL,
    opcion_id bigint,
    valor_texto text,
    valor_numero numeric(14,4),
    valor_booleano boolean,
    valor_fecha date,
    nivel_verificacion character varying(255) DEFAULT 'declared'::character varying NOT NULL,
    creado_en timestamp(0) without time zone,
    actualizado_en timestamp(0) without time zone,
    CONSTRAINT valores_atributo_publicacion_nivel_verificacion_check CHECK (((nivel_verificacion)::text = ANY ((ARRAY['declared'::character varying, 'documented'::character varying, 'professional'::character varying])::text[])))
);


--
-- Name: valores_atributo_publicacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.valores_atributo_publicacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: valores_atributo_publicacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.valores_atributo_publicacion_id_seq OWNED BY public.valores_atributo_publicacion.id;


--
-- Name: vendedores_seguidos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vendedores_seguidos (
    id bigint NOT NULL,
    usuario_id bigint NOT NULL,
    vendedor_id bigint NOT NULL,
    creado_en timestamp(0) without time zone
);


--
-- Name: vendedores_seguidos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vendedores_seguidos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vendedores_seguidos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vendedores_seguidos_id_seq OWNED BY public.vendedores_seguidos.id;


--
-- Name: atributos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.atributos ALTER COLUMN id SET DEFAULT nextval('public.atributos_id_seq'::regclass);


--
-- Name: bitacora_auditoria id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bitacora_auditoria ALTER COLUMN id SET DEFAULT nextval('public.bitacora_auditoria_id_seq'::regclass);


--
-- Name: busquedas_guardadas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.busquedas_guardadas ALTER COLUMN id SET DEFAULT nextval('public.busquedas_guardadas_id_seq'::regclass);


--
-- Name: categorias id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categorias ALTER COLUMN id SET DEFAULT nextval('public.categorias_id_seq'::regclass);


--
-- Name: codigos_verificacion_contacto id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.codigos_verificacion_contacto ALTER COLUMN id SET DEFAULT nextval('public.codigos_verificacion_contacto_id_seq'::regclass);


--
-- Name: conversaciones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversaciones ALTER COLUMN id SET DEFAULT nextval('public.conversaciones_id_seq'::regclass);


--
-- Name: documentos_publicacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documentos_publicacion ALTER COLUMN id SET DEFAULT nextval('public.documentos_publicacion_id_seq'::regclass);


--
-- Name: documentos_verificacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documentos_verificacion ALTER COLUMN id SET DEFAULT nextval('public.documentos_verificacion_id_seq'::regclass);


--
-- Name: eventos_operacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.eventos_operacion ALTER COLUMN id SET DEFAULT nextval('public.eventos_operacion_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: favoritos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.favoritos ALTER COLUMN id SET DEFAULT nextval('public.favoritos_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: medios_publicacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medios_publicacion ALTER COLUMN id SET DEFAULT nextval('public.medios_publicacion_id_seq'::regclass);


--
-- Name: mensajes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mensajes ALTER COLUMN id SET DEFAULT nextval('public.mensajes_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: ofertas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ofertas ALTER COLUMN id SET DEFAULT nextval('public.ofertas_id_seq'::regclass);


--
-- Name: opciones_atributo id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.opciones_atributo ALTER COLUMN id SET DEFAULT nextval('public.opciones_atributo_id_seq'::regclass);


--
-- Name: operaciones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.operaciones ALTER COLUMN id SET DEFAULT nextval('public.operaciones_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: predios id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.predios ALTER COLUMN id SET DEFAULT nextval('public.predios_id_seq'::regclass);


--
-- Name: publicaciones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.publicaciones ALTER COLUMN id SET DEFAULT nextval('public.publicaciones_id_seq'::regclass);


--
-- Name: reglas_cumplimiento id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reglas_cumplimiento ALTER COLUMN id SET DEFAULT nextval('public.reglas_cumplimiento_id_seq'::regclass);


--
-- Name: reportes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reportes ALTER COLUMN id SET DEFAULT nextval('public.reportes_id_seq'::regclass);


--
-- Name: resenas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenas ALTER COLUMN id SET DEFAULT nextval('public.resenas_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: solicitudes_verificacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.solicitudes_verificacion ALTER COLUMN id SET DEFAULT nextval('public.solicitudes_verificacion_id_seq'::regclass);


--
-- Name: tipo_producto_atributos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipo_producto_atributos ALTER COLUMN id SET DEFAULT nextval('public.tipo_producto_atributos_id_seq'::regclass);


--
-- Name: tipos_producto id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipos_producto ALTER COLUMN id SET DEFAULT nextval('public.tipos_producto_id_seq'::regclass);


--
-- Name: tokens_dispositivo id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tokens_dispositivo ALTER COLUMN id SET DEFAULT nextval('public.tokens_dispositivo_id_seq'::regclass);


--
-- Name: usuarios id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios ALTER COLUMN id SET DEFAULT nextval('public.usuarios_id_seq'::regclass);


--
-- Name: valores_atributo_publicacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.valores_atributo_publicacion ALTER COLUMN id SET DEFAULT nextval('public.valores_atributo_publicacion_id_seq'::regclass);


--
-- Name: vendedores_seguidos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendedores_seguidos ALTER COLUMN id SET DEFAULT nextval('public.vendedores_seguidos_id_seq'::regclass);


--
-- Name: atributos atributos_clave_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.atributos
    ADD CONSTRAINT atributos_clave_unique UNIQUE (clave);


--
-- Name: atributos atributos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.atributos
    ADD CONSTRAINT atributos_pkey PRIMARY KEY (id);


--
-- Name: bitacora_auditoria bitacora_auditoria_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bitacora_auditoria
    ADD CONSTRAINT bitacora_auditoria_pkey PRIMARY KEY (id);


--
-- Name: busquedas_guardadas busquedas_guardadas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.busquedas_guardadas
    ADD CONSTRAINT busquedas_guardadas_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: categorias categorias_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_pkey PRIMARY KEY (id);


--
-- Name: categorias categorias_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_slug_unique UNIQUE (slug);


--
-- Name: codigos_verificacion_contacto codigos_verificacion_contacto_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.codigos_verificacion_contacto
    ADD CONSTRAINT codigos_verificacion_contacto_pkey PRIMARY KEY (id);


--
-- Name: conversaciones conversaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversaciones
    ADD CONSTRAINT conversaciones_pkey PRIMARY KEY (id);


--
-- Name: conversaciones conversaciones_publicacion_id_comprador_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversaciones
    ADD CONSTRAINT conversaciones_publicacion_id_comprador_id_unique UNIQUE (publicacion_id, comprador_id);


--
-- Name: documentos_publicacion documentos_publicacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documentos_publicacion
    ADD CONSTRAINT documentos_publicacion_pkey PRIMARY KEY (id);


--
-- Name: documentos_verificacion documentos_verificacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documentos_verificacion
    ADD CONSTRAINT documentos_verificacion_pkey PRIMARY KEY (id);


--
-- Name: eventos_operacion eventos_operacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.eventos_operacion
    ADD CONSTRAINT eventos_operacion_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: favoritos favoritos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.favoritos
    ADD CONSTRAINT favoritos_pkey PRIMARY KEY (id);


--
-- Name: favoritos favoritos_usuario_id_publicacion_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.favoritos
    ADD CONSTRAINT favoritos_usuario_id_publicacion_id_unique UNIQUE (usuario_id, publicacion_id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: medios_publicacion medios_publicacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medios_publicacion
    ADD CONSTRAINT medios_publicacion_pkey PRIMARY KEY (id);


--
-- Name: mensajes mensajes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mensajes
    ADD CONSTRAINT mensajes_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type);


--
-- Name: notificaciones notificaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notificaciones
    ADD CONSTRAINT notificaciones_pkey PRIMARY KEY (id);


--
-- Name: ofertas ofertas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ofertas
    ADD CONSTRAINT ofertas_pkey PRIMARY KEY (id);


--
-- Name: opciones_atributo opciones_atributo_atributo_id_valor_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.opciones_atributo
    ADD CONSTRAINT opciones_atributo_atributo_id_valor_unique UNIQUE (atributo_id, valor);


--
-- Name: opciones_atributo opciones_atributo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.opciones_atributo
    ADD CONSTRAINT opciones_atributo_pkey PRIMARY KEY (id);


--
-- Name: operaciones operaciones_oferta_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.operaciones
    ADD CONSTRAINT operaciones_oferta_id_unique UNIQUE (oferta_id);


--
-- Name: operaciones operaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.operaciones
    ADD CONSTRAINT operaciones_pkey PRIMARY KEY (id);


--
-- Name: perfiles perfiles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.perfiles
    ADD CONSTRAINT perfiles_pkey PRIMARY KEY (usuario_id);


--
-- Name: perfiles_vendedor perfiles_vendedor_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.perfiles_vendedor
    ADD CONSTRAINT perfiles_vendedor_pkey PRIMARY KEY (usuario_id);


--
-- Name: permissions permissions_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: predios predios_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.predios
    ADD CONSTRAINT predios_pkey PRIMARY KEY (id);


--
-- Name: publicaciones publicaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.publicaciones
    ADD CONSTRAINT publicaciones_pkey PRIMARY KEY (id);


--
-- Name: publicaciones publicaciones_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.publicaciones
    ADD CONSTRAINT publicaciones_slug_unique UNIQUE (slug);


--
-- Name: reglas_cumplimiento reglas_cumplimiento_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reglas_cumplimiento
    ADD CONSTRAINT reglas_cumplimiento_pkey PRIMARY KEY (id);


--
-- Name: reportes reportes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reportes
    ADD CONSTRAINT reportes_pkey PRIMARY KEY (id);


--
-- Name: resenas resenas_operacion_id_autor_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenas
    ADD CONSTRAINT resenas_operacion_id_autor_id_unique UNIQUE (operacion_id, autor_id);


--
-- Name: resenas resenas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenas
    ADD CONSTRAINT resenas_pkey PRIMARY KEY (id);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: roles roles_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: solicitudes_verificacion solicitudes_verificacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.solicitudes_verificacion
    ADD CONSTRAINT solicitudes_verificacion_pkey PRIMARY KEY (id);


--
-- Name: tipo_producto_atributos tipo_producto_atributos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipo_producto_atributos
    ADD CONSTRAINT tipo_producto_atributos_pkey PRIMARY KEY (id);


--
-- Name: tipo_producto_atributos tipo_producto_atributos_tipo_producto_id_atributo_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipo_producto_atributos
    ADD CONSTRAINT tipo_producto_atributos_tipo_producto_id_atributo_id_unique UNIQUE (tipo_producto_id, atributo_id);


--
-- Name: tipos_producto tipos_producto_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipos_producto
    ADD CONSTRAINT tipos_producto_pkey PRIMARY KEY (id);


--
-- Name: tipos_producto tipos_producto_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipos_producto
    ADD CONSTRAINT tipos_producto_slug_unique UNIQUE (slug);


--
-- Name: tokens_dispositivo tokens_dispositivo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tokens_dispositivo
    ADD CONSTRAINT tokens_dispositivo_pkey PRIMARY KEY (id);


--
-- Name: tokens_dispositivo tokens_dispositivo_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tokens_dispositivo
    ADD CONSTRAINT tokens_dispositivo_token_unique UNIQUE (token);


--
-- Name: ubicaciones_publicacion ubicaciones_publicacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ubicaciones_publicacion
    ADD CONSTRAINT ubicaciones_publicacion_pkey PRIMARY KEY (publicacion_id);


--
-- Name: usuarios usuarios_correo_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_correo_unique UNIQUE (correo);


--
-- Name: usuarios usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id);


--
-- Name: valores_atributo_publicacion valores_atributo_publicacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.valores_atributo_publicacion
    ADD CONSTRAINT valores_atributo_publicacion_pkey PRIMARY KEY (id);


--
-- Name: valores_atributo_publicacion valores_atributo_publicacion_publicacion_id_atributo_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.valores_atributo_publicacion
    ADD CONSTRAINT valores_atributo_publicacion_publicacion_id_atributo_id_unique UNIQUE (publicacion_id, atributo_id);


--
-- Name: vendedores_seguidos vendedores_seguidos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendedores_seguidos
    ADD CONSTRAINT vendedores_seguidos_pkey PRIMARY KEY (id);


--
-- Name: vendedores_seguidos vendedores_seguidos_usuario_id_vendedor_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendedores_seguidos
    ADD CONSTRAINT vendedores_seguidos_usuario_id_vendedor_id_unique UNIQUE (usuario_id, vendedor_id);


--
-- Name: bitacora_auditoria_auditable_tipo_auditable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bitacora_auditoria_auditable_tipo_auditable_id_index ON public.bitacora_auditoria USING btree (auditable_tipo, auditable_id);


--
-- Name: bitacora_auditoria_usuario_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bitacora_auditoria_usuario_id_index ON public.bitacora_auditoria USING btree (usuario_id);


--
-- Name: busquedas_guardadas_usuario_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX busquedas_guardadas_usuario_id_index ON public.busquedas_guardadas USING btree (usuario_id);


--
-- Name: codigos_verificacion_contacto_usuario_id_canal_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX codigos_verificacion_contacto_usuario_id_canal_index ON public.codigos_verificacion_contacto USING btree (usuario_id, canal);


--
-- Name: conversaciones_vendedor_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX conversaciones_vendedor_id_index ON public.conversaciones USING btree (vendedor_id);


--
-- Name: documentos_publicacion_publicacion_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX documentos_publicacion_publicacion_id_index ON public.documentos_publicacion USING btree (publicacion_id);


--
-- Name: documentos_verificacion_solicitud_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX documentos_verificacion_solicitud_id_index ON public.documentos_verificacion USING btree (solicitud_id);


--
-- Name: eventos_operacion_operacion_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX eventos_operacion_operacion_id_index ON public.eventos_operacion USING btree (operacion_id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: medios_publicacion_publicacion_id_posicion_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX medios_publicacion_publicacion_id_posicion_index ON public.medios_publicacion USING btree (publicacion_id, posicion);


--
-- Name: mensajes_conversacion_id_creado_en_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mensajes_conversacion_id_creado_en_index ON public.mensajes USING btree (conversacion_id, creado_en);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: notificaciones_notificable_tipo_notificable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notificaciones_notificable_tipo_notificable_id_index ON public.notificaciones USING btree (notificable_tipo, notificable_id);


--
-- Name: ofertas_conversacion_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ofertas_conversacion_id_index ON public.ofertas USING btree (conversacion_id);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: predios_ubicacion_exacta_gix; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX predios_ubicacion_exacta_gix ON public.predios USING gist (ubicacion_exacta);


--
-- Name: predios_usuario_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX predios_usuario_id_index ON public.predios USING btree (usuario_id);


--
-- Name: publicaciones_atributos_cache_gin; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX publicaciones_atributos_cache_gin ON public.publicaciones USING gin (atributos_cache);


--
-- Name: publicaciones_busqueda_tsv_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX publicaciones_busqueda_tsv_idx ON public.publicaciones USING gin (to_tsvector('spanish'::regconfig, ((public.immutable_unaccent((titulo)::text) || ' '::text) || public.immutable_unaccent(COALESCE(descripcion, ''::text)))));


--
-- Name: publicaciones_estatus_tipo_pub_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX publicaciones_estatus_tipo_pub_idx ON public.publicaciones USING btree (estatus, tipo_producto_id, publicado_en);


--
-- Name: reportes_estatus_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reportes_estatus_index ON public.reportes USING btree (estatus);


--
-- Name: reportes_reportable_tipo_reportable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reportes_reportable_tipo_reportable_id_index ON public.reportes USING btree (reportable_tipo, reportable_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: solicitudes_verificacion_una_pendiente; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX solicitudes_verificacion_una_pendiente ON public.solicitudes_verificacion USING btree (usuario_id) WHERE ((estatus)::text = 'pendiente'::text);


--
-- Name: solicitudes_verificacion_usuario_id_estatus_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX solicitudes_verificacion_usuario_id_estatus_index ON public.solicitudes_verificacion USING btree (usuario_id, estatus);


--
-- Name: tipos_producto_categoria_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tipos_producto_categoria_id_index ON public.tipos_producto USING btree (categoria_id);


--
-- Name: tokens_dispositivo_usuario_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tokens_dispositivo_usuario_id_index ON public.tokens_dispositivo USING btree (usuario_id);


--
-- Name: ubicaciones_publicacion_aprox_gix; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ubicaciones_publicacion_aprox_gix ON public.ubicaciones_publicacion USING gist (ubicacion_aproximada);


--
-- Name: vap_atributo_numero_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vap_atributo_numero_idx ON public.valores_atributo_publicacion USING btree (atributo_id, valor_numero);


--
-- Name: bitacora_auditoria bitacora_auditoria_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bitacora_auditoria
    ADD CONSTRAINT bitacora_auditoria_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE SET NULL;


--
-- Name: busquedas_guardadas busquedas_guardadas_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.busquedas_guardadas
    ADD CONSTRAINT busquedas_guardadas_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: categorias categorias_categoria_padre_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_categoria_padre_id_foreign FOREIGN KEY (categoria_padre_id) REFERENCES public.categorias(id) ON DELETE SET NULL;


--
-- Name: codigos_verificacion_contacto codigos_verificacion_contacto_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.codigos_verificacion_contacto
    ADD CONSTRAINT codigos_verificacion_contacto_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: conversaciones conversaciones_comprador_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversaciones
    ADD CONSTRAINT conversaciones_comprador_id_foreign FOREIGN KEY (comprador_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: conversaciones conversaciones_publicacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversaciones
    ADD CONSTRAINT conversaciones_publicacion_id_foreign FOREIGN KEY (publicacion_id) REFERENCES public.publicaciones(id) ON DELETE CASCADE;


--
-- Name: conversaciones conversaciones_vendedor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversaciones
    ADD CONSTRAINT conversaciones_vendedor_id_foreign FOREIGN KEY (vendedor_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: documentos_publicacion documentos_publicacion_publicacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documentos_publicacion
    ADD CONSTRAINT documentos_publicacion_publicacion_id_foreign FOREIGN KEY (publicacion_id) REFERENCES public.publicaciones(id) ON DELETE CASCADE;


--
-- Name: documentos_publicacion documentos_publicacion_revisado_por_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documentos_publicacion
    ADD CONSTRAINT documentos_publicacion_revisado_por_foreign FOREIGN KEY (revisado_por) REFERENCES public.usuarios(id) ON DELETE SET NULL;


--
-- Name: documentos_verificacion documentos_verificacion_solicitud_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documentos_verificacion
    ADD CONSTRAINT documentos_verificacion_solicitud_id_foreign FOREIGN KEY (solicitud_id) REFERENCES public.solicitudes_verificacion(id) ON DELETE CASCADE;


--
-- Name: eventos_operacion eventos_operacion_actor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.eventos_operacion
    ADD CONSTRAINT eventos_operacion_actor_id_foreign FOREIGN KEY (actor_id) REFERENCES public.usuarios(id) ON DELETE SET NULL;


--
-- Name: eventos_operacion eventos_operacion_operacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.eventos_operacion
    ADD CONSTRAINT eventos_operacion_operacion_id_foreign FOREIGN KEY (operacion_id) REFERENCES public.operaciones(id) ON DELETE CASCADE;


--
-- Name: favoritos favoritos_publicacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.favoritos
    ADD CONSTRAINT favoritos_publicacion_id_foreign FOREIGN KEY (publicacion_id) REFERENCES public.publicaciones(id) ON DELETE CASCADE;


--
-- Name: favoritos favoritos_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.favoritos
    ADD CONSTRAINT favoritos_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: medios_publicacion medios_publicacion_publicacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medios_publicacion
    ADD CONSTRAINT medios_publicacion_publicacion_id_foreign FOREIGN KEY (publicacion_id) REFERENCES public.publicaciones(id) ON DELETE CASCADE;


--
-- Name: mensajes mensajes_conversacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mensajes
    ADD CONSTRAINT mensajes_conversacion_id_foreign FOREIGN KEY (conversacion_id) REFERENCES public.conversaciones(id) ON DELETE CASCADE;


--
-- Name: mensajes mensajes_oferta_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mensajes
    ADD CONSTRAINT mensajes_oferta_id_foreign FOREIGN KEY (oferta_id) REFERENCES public.ofertas(id) ON DELETE SET NULL;


--
-- Name: mensajes mensajes_remitente_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mensajes
    ADD CONSTRAINT mensajes_remitente_id_foreign FOREIGN KEY (remitente_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: model_has_permissions model_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: ofertas ofertas_conversacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ofertas
    ADD CONSTRAINT ofertas_conversacion_id_foreign FOREIGN KEY (conversacion_id) REFERENCES public.conversaciones(id) ON DELETE CASCADE;


--
-- Name: ofertas ofertas_remitente_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ofertas
    ADD CONSTRAINT ofertas_remitente_id_foreign FOREIGN KEY (remitente_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: opciones_atributo opciones_atributo_atributo_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.opciones_atributo
    ADD CONSTRAINT opciones_atributo_atributo_id_foreign FOREIGN KEY (atributo_id) REFERENCES public.atributos(id) ON DELETE CASCADE;


--
-- Name: operaciones operaciones_comprador_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.operaciones
    ADD CONSTRAINT operaciones_comprador_id_foreign FOREIGN KEY (comprador_id) REFERENCES public.usuarios(id) ON DELETE RESTRICT;


--
-- Name: operaciones operaciones_oferta_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.operaciones
    ADD CONSTRAINT operaciones_oferta_id_foreign FOREIGN KEY (oferta_id) REFERENCES public.ofertas(id) ON DELETE RESTRICT;


--
-- Name: operaciones operaciones_publicacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.operaciones
    ADD CONSTRAINT operaciones_publicacion_id_foreign FOREIGN KEY (publicacion_id) REFERENCES public.publicaciones(id) ON DELETE RESTRICT;


--
-- Name: operaciones operaciones_vendedor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.operaciones
    ADD CONSTRAINT operaciones_vendedor_id_foreign FOREIGN KEY (vendedor_id) REFERENCES public.usuarios(id) ON DELETE RESTRICT;


--
-- Name: perfiles perfiles_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.perfiles
    ADD CONSTRAINT perfiles_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: perfiles_vendedor perfiles_vendedor_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.perfiles_vendedor
    ADD CONSTRAINT perfiles_vendedor_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: predios predios_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.predios
    ADD CONSTRAINT predios_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: publicaciones publicaciones_predio_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.publicaciones
    ADD CONSTRAINT publicaciones_predio_id_foreign FOREIGN KEY (predio_id) REFERENCES public.predios(id) ON DELETE SET NULL;


--
-- Name: publicaciones publicaciones_tipo_producto_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.publicaciones
    ADD CONSTRAINT publicaciones_tipo_producto_id_foreign FOREIGN KEY (tipo_producto_id) REFERENCES public.tipos_producto(id) ON DELETE RESTRICT;


--
-- Name: publicaciones publicaciones_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.publicaciones
    ADD CONSTRAINT publicaciones_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: reglas_cumplimiento reglas_cumplimiento_categoria_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reglas_cumplimiento
    ADD CONSTRAINT reglas_cumplimiento_categoria_id_foreign FOREIGN KEY (categoria_id) REFERENCES public.categorias(id) ON DELETE CASCADE;


--
-- Name: reglas_cumplimiento reglas_cumplimiento_tipo_producto_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reglas_cumplimiento
    ADD CONSTRAINT reglas_cumplimiento_tipo_producto_id_foreign FOREIGN KEY (tipo_producto_id) REFERENCES public.tipos_producto(id) ON DELETE CASCADE;


--
-- Name: reportes reportes_reportante_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reportes
    ADD CONSTRAINT reportes_reportante_id_foreign FOREIGN KEY (reportante_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: reportes reportes_resuelto_por_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reportes
    ADD CONSTRAINT reportes_resuelto_por_foreign FOREIGN KEY (resuelto_por) REFERENCES public.usuarios(id) ON DELETE SET NULL;


--
-- Name: resenas resenas_autor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenas
    ADD CONSTRAINT resenas_autor_id_foreign FOREIGN KEY (autor_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: resenas resenas_evaluado_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenas
    ADD CONSTRAINT resenas_evaluado_id_foreign FOREIGN KEY (evaluado_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: resenas resenas_operacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenas
    ADD CONSTRAINT resenas_operacion_id_foreign FOREIGN KEY (operacion_id) REFERENCES public.operaciones(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: solicitudes_verificacion solicitudes_verificacion_revisado_por_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.solicitudes_verificacion
    ADD CONSTRAINT solicitudes_verificacion_revisado_por_foreign FOREIGN KEY (revisado_por) REFERENCES public.usuarios(id) ON DELETE SET NULL;


--
-- Name: solicitudes_verificacion solicitudes_verificacion_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.solicitudes_verificacion
    ADD CONSTRAINT solicitudes_verificacion_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: tipo_producto_atributos tipo_producto_atributos_atributo_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipo_producto_atributos
    ADD CONSTRAINT tipo_producto_atributos_atributo_id_foreign FOREIGN KEY (atributo_id) REFERENCES public.atributos(id) ON DELETE CASCADE;


--
-- Name: tipo_producto_atributos tipo_producto_atributos_tipo_producto_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipo_producto_atributos
    ADD CONSTRAINT tipo_producto_atributos_tipo_producto_id_foreign FOREIGN KEY (tipo_producto_id) REFERENCES public.tipos_producto(id) ON DELETE CASCADE;


--
-- Name: tipos_producto tipos_producto_categoria_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipos_producto
    ADD CONSTRAINT tipos_producto_categoria_id_foreign FOREIGN KEY (categoria_id) REFERENCES public.categorias(id) ON DELETE CASCADE;


--
-- Name: tokens_dispositivo tokens_dispositivo_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tokens_dispositivo
    ADD CONSTRAINT tokens_dispositivo_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: ubicaciones_publicacion ubicaciones_publicacion_predio_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ubicaciones_publicacion
    ADD CONSTRAINT ubicaciones_publicacion_predio_id_foreign FOREIGN KEY (predio_id) REFERENCES public.predios(id) ON DELETE SET NULL;


--
-- Name: ubicaciones_publicacion ubicaciones_publicacion_publicacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ubicaciones_publicacion
    ADD CONSTRAINT ubicaciones_publicacion_publicacion_id_foreign FOREIGN KEY (publicacion_id) REFERENCES public.publicaciones(id) ON DELETE CASCADE;


--
-- Name: valores_atributo_publicacion valores_atributo_publicacion_atributo_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.valores_atributo_publicacion
    ADD CONSTRAINT valores_atributo_publicacion_atributo_id_foreign FOREIGN KEY (atributo_id) REFERENCES public.atributos(id) ON DELETE CASCADE;


--
-- Name: valores_atributo_publicacion valores_atributo_publicacion_opcion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.valores_atributo_publicacion
    ADD CONSTRAINT valores_atributo_publicacion_opcion_id_foreign FOREIGN KEY (opcion_id) REFERENCES public.opciones_atributo(id) ON DELETE SET NULL;


--
-- Name: valores_atributo_publicacion valores_atributo_publicacion_publicacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.valores_atributo_publicacion
    ADD CONSTRAINT valores_atributo_publicacion_publicacion_id_foreign FOREIGN KEY (publicacion_id) REFERENCES public.publicaciones(id) ON DELETE CASCADE;


--
-- Name: vendedores_seguidos vendedores_seguidos_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendedores_seguidos
    ADD CONSTRAINT vendedores_seguidos_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: vendedores_seguidos vendedores_seguidos_vendedor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendedores_seguidos
    ADD CONSTRAINT vendedores_seguidos_vendedor_id_foreign FOREIGN KEY (vendedor_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict 6VGZj91gQI0rrzSPXEeAU3YlzsUUfos5FucyPLAeAOTv7Eho1hwgJTteLEPtU6v

