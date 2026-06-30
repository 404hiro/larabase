--
-- PostgreSQL database dump
--

\restrict 7hIFT7bq3GdYeHRzsurdxCuydz9Qn12gjG0nTovHzLHkhAbgfXQmrZANGRzPY7W

-- Dumped from database version 17.9
-- Dumped by pg_dump version 17.9 (Ubuntu 17.9-1.pgdg24.04+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

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
-- Name: link_view_daily_stats; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.link_view_daily_stats (
    id bigint NOT NULL,
    link_id uuid NOT NULL,
    date date NOT NULL,
    view_count integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: link_view_daily_stats_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.link_view_daily_stats_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: link_view_daily_stats_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.link_view_daily_stats_id_seq OWNED BY public.link_view_daily_stats.id;


--
-- Name: links; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.links (
    user_id bigint NOT NULL,
    slug character varying(255) NOT NULL,
    display_name character varying(255) NOT NULL,
    bio text,
    avatar_url character varying(2048),
    theme_config json,
    is_published boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    title_id bigint,
    has_web_display boolean DEFAULT false NOT NULL,
    is_accepting_messages boolean DEFAULT true NOT NULL,
    message_settings json,
    id uuid NOT NULL
);


--
-- Name: message_blocks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.message_blocks (
    id bigint NOT NULL,
    blocked_user_id bigint NOT NULL,
    reason character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    link_id uuid
);


--
-- Name: message_blocks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.message_blocks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: message_blocks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.message_blocks_id_seq OWNED BY public.message_blocks.id;


--
-- Name: message_replies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.message_replies (
    id bigint NOT NULL,
    message_id uuid NOT NULL,
    body text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: message_replies_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.message_replies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: message_replies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.message_replies_id_seq OWNED BY public.message_replies.id;


--
-- Name: message_reports; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.message_reports (
    id bigint NOT NULL,
    message_id uuid NOT NULL,
    reporter_user_id bigint NOT NULL,
    reason character varying(64) NOT NULL,
    detail text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: message_reports_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.message_reports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: message_reports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.message_reports_id_seq OWNED BY public.message_reports.id;


--
-- Name: messages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.messages (
    id uuid NOT NULL,
    sender_user_id bigint,
    body text NOT NULL,
    sender_mode character varying(32) DEFAULT 'anonymous'::character varying NOT NULL,
    sender_display_name character varying(255),
    status character varying(32) DEFAULT 'safe'::character varying NOT NULL,
    is_public boolean DEFAULT false NOT NULL,
    published_at timestamp(0) without time zone,
    read_at timestamp(0) without time zone,
    metadata json,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    amount integer DEFAULT 0 NOT NULL,
    link_id uuid,
    is_read boolean DEFAULT false NOT NULL
);


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
-- Name: notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    type character varying(255) NOT NULL,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint NOT NULL,
    data text NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
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
-- Name: titles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.titles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: titles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.titles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: titles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.titles_id_seq OWNED BY public.titles.id;


--
-- Name: user_stripe_accounts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_stripe_accounts (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    stripe_account_id character varying(255) NOT NULL,
    details_submitted boolean DEFAULT false NOT NULL,
    charges_enabled boolean DEFAULT false NOT NULL,
    payouts_enabled boolean DEFAULT false NOT NULL,
    onboarding_completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_stripe_accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_stripe_accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_stripe_accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_stripe_accounts_id_seq OWNED BY public.user_stripe_accounts.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    avatar character varying(2048),
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    two_factor_secret text,
    two_factor_recovery_codes text,
    two_factor_confirmed_at timestamp(0) without time zone,
    google_id character varying(255) NOT NULL
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: widget_click_daily_stats; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.widget_click_daily_stats (
    id bigint NOT NULL,
    link_id uuid NOT NULL,
    widget_id bigint NOT NULL,
    date date NOT NULL,
    click_count integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: widget_click_daily_stats_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.widget_click_daily_stats_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: widget_click_daily_stats_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.widget_click_daily_stats_id_seq OWNED BY public.widget_click_daily_stats.id;


--
-- Name: widgets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.widgets (
    id bigint NOT NULL,
    type character varying(255) DEFAULT 'link'::character varying NOT NULL,
    content text,
    thumbnail_url character varying(2048),
    x integer DEFAULT 0 NOT NULL,
    y integer DEFAULT 0 NOT NULL,
    w integer DEFAULT 1 NOT NULL,
    h integer DEFAULT 1 NOT NULL,
    x_mobile integer DEFAULT 0 NOT NULL,
    y_mobile integer DEFAULT 0 NOT NULL,
    w_mobile integer DEFAULT 1 NOT NULL,
    h_mobile integer DEFAULT 1 NOT NULL,
    settings json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    link_id uuid
);


--
-- Name: widgets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.widgets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: widgets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.widgets_id_seq OWNED BY public.widgets.id;


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: link_view_daily_stats id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.link_view_daily_stats ALTER COLUMN id SET DEFAULT nextval('public.link_view_daily_stats_id_seq'::regclass);


--
-- Name: message_blocks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_blocks ALTER COLUMN id SET DEFAULT nextval('public.message_blocks_id_seq'::regclass);


--
-- Name: message_replies id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_replies ALTER COLUMN id SET DEFAULT nextval('public.message_replies_id_seq'::regclass);


--
-- Name: message_reports id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_reports ALTER COLUMN id SET DEFAULT nextval('public.message_reports_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: titles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.titles ALTER COLUMN id SET DEFAULT nextval('public.titles_id_seq'::regclass);


--
-- Name: user_stripe_accounts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_stripe_accounts ALTER COLUMN id SET DEFAULT nextval('public.user_stripe_accounts_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: widget_click_daily_stats id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widget_click_daily_stats ALTER COLUMN id SET DEFAULT nextval('public.widget_click_daily_stats_id_seq'::regclass);


--
-- Name: widgets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widgets ALTER COLUMN id SET DEFAULT nextval('public.widgets_id_seq'::regclass);


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
-- Name: link_view_daily_stats link_view_daily_stats_link_id_date_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.link_view_daily_stats
    ADD CONSTRAINT link_view_daily_stats_link_id_date_unique UNIQUE (link_id, date);


--
-- Name: link_view_daily_stats link_view_daily_stats_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.link_view_daily_stats
    ADD CONSTRAINT link_view_daily_stats_pkey PRIMARY KEY (id);


--
-- Name: links links_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.links
    ADD CONSTRAINT links_pkey PRIMARY KEY (id);


--
-- Name: links links_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.links
    ADD CONSTRAINT links_slug_unique UNIQUE (slug);


--
-- Name: links links_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.links
    ADD CONSTRAINT links_uuid_unique UNIQUE (id);


--
-- Name: message_blocks message_blocks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_blocks
    ADD CONSTRAINT message_blocks_pkey PRIMARY KEY (id);


--
-- Name: message_replies message_replies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_replies
    ADD CONSTRAINT message_replies_pkey PRIMARY KEY (id);


--
-- Name: message_reports message_reports_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_reports
    ADD CONSTRAINT message_reports_pkey PRIMARY KEY (id);


--
-- Name: messages messages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_pkey PRIMARY KEY (id);


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
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


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
-- Name: titles titles_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.titles
    ADD CONSTRAINT titles_name_unique UNIQUE (name);


--
-- Name: titles titles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.titles
    ADD CONSTRAINT titles_pkey PRIMARY KEY (id);


--
-- Name: user_stripe_accounts user_stripe_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_stripe_accounts
    ADD CONSTRAINT user_stripe_accounts_pkey PRIMARY KEY (id);


--
-- Name: user_stripe_accounts user_stripe_accounts_stripe_account_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_stripe_accounts
    ADD CONSTRAINT user_stripe_accounts_stripe_account_id_unique UNIQUE (stripe_account_id);


--
-- Name: user_stripe_accounts user_stripe_accounts_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_stripe_accounts
    ADD CONSTRAINT user_stripe_accounts_user_id_unique UNIQUE (user_id);


--
-- Name: users users_google_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_google_id_unique UNIQUE (google_id);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: widget_click_daily_stats widget_click_daily_stats_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widget_click_daily_stats
    ADD CONSTRAINT widget_click_daily_stats_pkey PRIMARY KEY (id);


--
-- Name: widget_click_daily_stats widget_click_daily_stats_widget_id_date_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widget_click_daily_stats
    ADD CONSTRAINT widget_click_daily_stats_widget_id_date_unique UNIQUE (widget_id, date);


--
-- Name: widgets widgets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widgets
    ADD CONSTRAINT widgets_pkey PRIMARY KEY (id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: link_view_daily_stats_link_id_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX link_view_daily_stats_link_id_date_index ON public.link_view_daily_stats USING btree (link_id, date);


--
-- Name: links_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX links_user_id_created_at_index ON public.links USING btree (user_id, created_at);


--
-- Name: links_user_id_is_published_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX links_user_id_is_published_index ON public.links USING btree (user_id, is_published);


--
-- Name: messages_link_id_is_public_is_read_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX messages_link_id_is_public_is_read_index ON public.messages USING btree (link_id, is_public, is_read);


--
-- Name: messages_sender_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX messages_sender_user_id_index ON public.messages USING btree (sender_user_id);


--
-- Name: messages_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX messages_status_index ON public.messages USING btree (status);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: notifications_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: titles_is_active_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX titles_is_active_sort_order_index ON public.titles USING btree (is_active, sort_order);


--
-- Name: widget_click_daily_stats_link_id_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX widget_click_daily_stats_link_id_date_index ON public.widget_click_daily_stats USING btree (link_id, date);


--
-- Name: widget_click_daily_stats_widget_id_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX widget_click_daily_stats_widget_id_date_index ON public.widget_click_daily_stats USING btree (widget_id, date);


--
-- Name: link_view_daily_stats link_view_daily_stats_link_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.link_view_daily_stats
    ADD CONSTRAINT link_view_daily_stats_link_id_foreign FOREIGN KEY (link_id) REFERENCES public.links(id) ON DELETE CASCADE;


--
-- Name: links links_title_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.links
    ADD CONSTRAINT links_title_id_foreign FOREIGN KEY (title_id) REFERENCES public.titles(id) ON DELETE SET NULL;


--
-- Name: links links_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.links
    ADD CONSTRAINT links_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: message_blocks message_blocks_blocked_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_blocks
    ADD CONSTRAINT message_blocks_blocked_user_id_foreign FOREIGN KEY (blocked_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: message_blocks message_blocks_link_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_blocks
    ADD CONSTRAINT message_blocks_link_id_foreign FOREIGN KEY (link_id) REFERENCES public.links(id) ON DELETE CASCADE;


--
-- Name: message_replies message_replies_message_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_replies
    ADD CONSTRAINT message_replies_message_id_foreign FOREIGN KEY (message_id) REFERENCES public.messages(id) ON DELETE CASCADE;


--
-- Name: message_reports message_reports_message_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_reports
    ADD CONSTRAINT message_reports_message_id_foreign FOREIGN KEY (message_id) REFERENCES public.messages(id) ON DELETE CASCADE;


--
-- Name: message_reports message_reports_reporter_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_reports
    ADD CONSTRAINT message_reports_reporter_user_id_foreign FOREIGN KEY (reporter_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: messages messages_link_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_link_id_foreign FOREIGN KEY (link_id) REFERENCES public.links(id) ON DELETE CASCADE;


--
-- Name: messages messages_sender_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_sender_user_id_foreign FOREIGN KEY (sender_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


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
-- Name: sessions sessions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_stripe_accounts user_stripe_accounts_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_stripe_accounts
    ADD CONSTRAINT user_stripe_accounts_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: widget_click_daily_stats widget_click_daily_stats_link_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widget_click_daily_stats
    ADD CONSTRAINT widget_click_daily_stats_link_id_foreign FOREIGN KEY (link_id) REFERENCES public.links(id) ON DELETE CASCADE;


--
-- Name: widget_click_daily_stats widget_click_daily_stats_widget_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widget_click_daily_stats
    ADD CONSTRAINT widget_click_daily_stats_widget_id_foreign FOREIGN KEY (widget_id) REFERENCES public.widgets(id) ON DELETE CASCADE;


--
-- Name: widgets widgets_link_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widgets
    ADD CONSTRAINT widgets_link_id_foreign FOREIGN KEY (link_id) REFERENCES public.links(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict 7hIFT7bq3GdYeHRzsurdxCuydz9Qn12gjG0nTovHzLHkhAbgfXQmrZANGRzPY7W

--
-- PostgreSQL database dump
--

\restrict Zf4EeLMCVcsJEJqgZpfs6JzYF3aIWbG7Hjpfme0cxyamvHgl2XAaos4KtfZRaOc

-- Dumped from database version 17.9
-- Dumped by pg_dump version 17.9 (Ubuntu 17.9-1.pgdg24.04+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2025_08_14_170933_add_two_factor_columns_to_users_table	1
5	2025_10_26_203602_create_permission_tables	1
6	2026_04_29_000000_create_links_table	1
7	2026_04_29_000001_drop_account_from_users_table	1
8	2026_05_07_000000_create_titles_table	1
9	2026_05_09_124434_add_google_id_to_users_table	1
10	2026_05_09_131054_update_users_table_for_google_only	1
11	2026_05_14_000000_add_has_web_display_to_links_table	1
12	2026_05_14_000001_drop_link_contacts_table	1
13	2026_05_16_130206_add_message_settings_to_links_table	1
14	2026_05_16_130207_create_messages_table	1
15	2026_05_16_130208_create_message_publications_table	1
16	2026_05_16_130209_create_message_blocks_table	1
17	2026_05_16_130210_create_message_reports_table	1
18	2026_05_16_130556_add_amount_to_messages_table	1
19	2026_05_16_142142_change_links_id_to_uuid	1
20	2026_05_17_000001_add_uuid_to_messages_table	1
21	2026_05_17_000002_change_messages_id_to_uuid	1
22	2026_05_17_000003_add_read_and_open_flags_to_messages_table	1
23	2026_05_17_131303_create_stats_tables	1
24	2026_05_17_142612_create_notifications_table	1
25	2026_05_17_171608_create_message_replies_table	1
26	2026_05_17_171609_drop_message_publications_table	1
27	2026_05_30_110334_add_cascade_to_sessions_table	2
28	2026_05_31_101957_update_messages_sender_cascade	3
29	2026_05_31_120000_create_user_stripe_accounts_table	4
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 29, true);


--
-- PostgreSQL database dump complete
--

\unrestrict Zf4EeLMCVcsJEJqgZpfs6JzYF3aIWbG7Hjpfme0cxyamvHgl2XAaos4KtfZRaOc

