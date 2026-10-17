export type Urgency = 'ok' | 'caution' | 'stop';

export type PlanValue = 'free' | 'pro' | 'tuner' | 'garage';

export type VehicleSummary = {
    uuid: string;
    name: string;
    make: string;
    model: string;
    year: number | null;
    engine: string | null;
    tuning_stage: string;
    tuning_label: string;
};

export type RunSummary = {
    uuid: string;
    mode: string;
    mode_label: string;
    result_s: number | null;
    trap_kmh: number | null;
    power_hp: number | null;
    torque_nm: number | null;
    is_record: boolean;
    source: string;
    recorded_at: string;
    conditions_summary: string | null;
    vehicle: VehicleSummary | null;
};

export type Split = {
    label: string;
    t_s: number;
    speed_kmh: number | null;
};

export type CurvePoint = {
    rpm: number;
    hp: number;
    nm: number;
};

export type RunConditions = {
    amb_c?: number;
    baro_hpa?: number;
    dry_hpa?: number;
    humidity?: number;
    iat_c?: number;
    ect_c?: number;
    mass_kg?: number;
    slope_pct?: number;
    sats?: number;
    gps_hz?: number;
    fuel_pct?: number;
};

export type RunDetail = RunSummary & {
    distance_m: number | null;
    sixty_ft_s: number | null;
    reaction_s?: number | null;
    splits: Split[];
    power_ci_hp?: number | null;
    peak_rpm: number | null;
    curve: CurvePoint[];
    conditions: RunConditions;
    source_label?: string;
    accuracy_s?: number;
    notes?: string | null;
    has_samples?: boolean;
};

export type DtcSummary = {
    uuid: string;
    code: string;
    kind: string;
    kind_label: string;
    title: string | null;
    system: string | null;
    urgency: Urgency | null;
    urgency_label: string | null;
    read_at: string;
    cleared_at: string | null;
    vehicle: VehicleSummary | null;
};

export type DtcCause = {
    label: string;
    probability: number;
    part: string | null;
    difficulty: string | null;
    cost_min: number | null;
    cost_max: number | null;
};

export type DtcExplanation = {
    code: string;
    title: string;
    system: string;
    explanation: string;
    urgency: Urgency;
    can_drive_note: string | null;
    causes: DtcCause[];
    source: 'kb' | 'ai';
    generic: boolean;
};

export type TripComponents = Partial<
    Record<
        | 'dtc'
        | 'misfire'
        | 'trims'
        | 'cooling'
        | 'electric'
        | 'readiness'
        | 'trends',
        number
    >
>;

export type TripSummary = {
    uuid: string;
    started_at: string;
    ended_at: string | null;
    distance_km: number;
    health_score: number;
    components: TripComponents;
    metrics: Record<string, number | null>;
    vehicle: VehicleSummary | null;
};

export type ShareSummary = {
    id: number;
    url: string;
    recipient: string | null;
    views: number;
    created_at: string | null;
    revoked_at: string | null;
    run: {
        uuid: string;
        mode_label: string;
        result_s: number | null;
        recorded_at: string;
    } | null;
};

export type Features = {
    all_modes: boolean;
    power: boolean;
    full_reports: boolean;
    unlimited_history: boolean;
    ai: boolean;
    csv_logs: boolean;
    compare: boolean;
    max_vehicles: number | null;
    runs_per_month: number | null;
};

export type Subscription = {
    plan: PlanValue;
    is_trial: boolean;
    trial_ends_at: string | null;
    plan_expires_at: string | null;
    plan_period: 'monthly' | 'yearly' | null;
    is_paid?: boolean;
    features: Features;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type Option = {
    value: string;
    label: string;
};
