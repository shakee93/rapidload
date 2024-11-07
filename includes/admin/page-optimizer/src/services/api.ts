import { isDev, toBoolean } from "lib/utils";
import store from "../store";
import { toast } from "components/ui/use-toast";
import { mockPageSpeed, mockSettings } from "./mock/data";

class ApiService {
    public baseURL: URL;
    private options: WordPressOptions;


    constructor(options?: WordPressOptions, query?: string, action?: string) {

        if (!options) {
            options = window.rapidload_optimizer
        }

        this.options = options

        let base = options?.ajax_url
            ? options.ajax_url
            : "http://rapidload.local/wp-admin/admin-ajax.php";

        const queryParams = new URLSearchParams(query);

        if (action) {
            queryParams.append("action", action);
        }

        if (options?.nonce && !queryParams.has('nonce')) {
            queryParams.append("nonce", options.nonce);
        }

        this.baseURL = new URL(base + "?" + queryParams.toString());

    }

    async throwIfError(response: Response, state: any = null) {

        if (!response.ok) {
            throw new Error("Oops! The request failed");
        }

        let data = await response.json();

        if (!data.success) {

            if (data.data.reload) {
                return data.data
            }

            if (Array.isArray(data?.data)) {

                throw new Error(
                    `[Code:${data.data[0].code}] ${data.data[0].detail ? data.data[0].detail : 'Internal error occurred on our end :('}`
                );
            }

            throw new Error(
                "Problem Retrieving Data: Our Apologies. For Assistance, Please Reach Out to Customer Support."
            );
        }

        return {
            ...data,
            state
        }
    }

    async fetchPageSpeed(url: string, activeReport: string, reload: boolean): Promise<any> {

        try {
            let data = await this.analyzeViaAPI(url, activeReport);

            if (data?.errors) {
                if (Array.isArray(data?.errors)) {
                    throw new Error(
                        `[Code:${data.errors[0].code}] ${data.errors[0].detail}`
                    );
                }

                throw new Error(
                    `Oops! Something went wrong :(`
                );
            }



            return {
                data: {
                    page_speed: data,
                    revisions: []
                },
                success: true
            }

        } catch (error) {
            console.error(error);
            throw error;
        }
    }

    async fetchSettings(url: string, activeReport: string, reload: boolean): Promise<any> {

        try {
            return mockSettings

        } catch (error) {
            console.error(error);
            toast({
                description: 'Failed to fetch RapidLoad settings!',
                variant: 'destructive',
            })
            throw error;
        }
    }

    async analyzeViaAPI(url: string, strategy: string) {

        try {
            const state = store.getState()
            const data = state.app.report[state.app.activeReport]
            const settings = state.app.settings.performance[state.app.activeReport]
            const testModeStatus = state.app.testMode?.status ?? state.app.settings.general.test_mode ?? false;
            const previewUrl = testModeStatus ? '?rapidload_preview' : '';

            const api_root = this.options?.api_root || 'https://api.rapidload.io/api/v1';
            const pageSpeedURL = new URL(`${api_root}/page-speed`);


            if (url.includes('demo.rapidload.io')) {
                return mockPageSpeed.data.page_speed
            }

            pageSpeedURL.searchParams.append('url', url + previewUrl)
            pageSpeedURL.searchParams.append('strategy', state.app.activeReport)
            pageSpeedURL.searchParams.append('plugin_version', this.options.rapidload_version)
            pageSpeedURL.searchParams.append('titan_version', __OPTIMIZER_VERSION__)

            if (this.options.license_key) {
                pageSpeedURL.searchParams.append('api_key', this.options.license_key);
            }

            const pageSpeed = await fetch(pageSpeedURL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    settings: settings.state?.
                        flatMap(t =>
                            t.inputs
                                .filter(({ value }) => value != null)
                                .map(({ key, value }) => ({ key, value, status: t.status })))
                        || []
                })
            });

            const data_ = await pageSpeed.json()
            console.log(data_)
            return data_

        } catch (error) {
            console.error(error);
            throw error;
        }
    }

    async getCSSJobStatus(url: string, types: string[]): Promise<any> {
        try {
            this.baseURL.searchParams.append('action', 'rapidload_css_job_status');
            this.baseURL.searchParams.append('url', url)
            this.baseURL.searchParams.append('types', types.join(','))

            const response = await fetch(this.baseURL, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            });
            return this.throwIfError(response);
        } catch (error) {
            console.error(error);
            throw error;
        }
    }

    async getTestMode(url: string, mode: boolean): Promise<any> {
        try {

            return {
                success: true,
                data: {
                    status: mode
                }
            }

            this.baseURL.searchParams.append('action', 'rapidload_switch_test_mode');
            this.baseURL.searchParams.append('url', url)
            this.baseURL.searchParams.append('test_mode', mode ? 'true' : 'false')

            const response = await fetch(this.baseURL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },

            });
            return this.throwIfError(response);
        } catch (error) {
            console.error(error);
            throw error;
        }
    }

    async updateSettings(url: string, activeReport: string, data: any, global: boolean, analyze: boolean) {
        try {

            const query = new URLSearchParams();

            if (this.baseURL.searchParams.get('action')) {
                this.baseURL.searchParams.delete('action')
            }

            this.baseURL.searchParams.append('action', 'update_titan_settings');

            if (global) this.baseURL.searchParams.append('global', 'true')
            if (analyze) this.baseURL.searchParams.append('analyze', 'true')

            this.baseURL.searchParams.append('url', url)
            this.baseURL.searchParams.append('strategy', activeReport)
            // this.baseURL.searchParams.append('settingsMode', settingsMode)

            const response = await fetch(this.baseURL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    settings: {
                        general: {
                            performance_gear: data.activeGear
                        },
                        performance: data.settings
                    },
                }),
            });



            return this.throwIfError(response);
        } catch (error) {
            console.error(error);
            throw error;
        }
    }

    async post(action: string | null = null, queryParams: { [p: string]: string } = {}) {

        try {

            if (action) {
                if (this.baseURL.searchParams.get('action')) {
                    this.baseURL.searchParams.delete('action')
                }
                this.baseURL.searchParams.append('action', action)
            }

            for (let key of Object.keys(queryParams)) {
                if (!this.baseURL.searchParams.has(key)) {
                    this.baseURL.searchParams.append(key, queryParams[key]);
                }
            }

            const response = await fetch(this.baseURL, {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                }
            });

            return this.throwIfError(response);
        } catch (error) {
            console.error(error);
            throw error;
        }
    }

    async request(endpoint: string,
        params: { [p: string]: string } = {},
        type: 'GET' | 'POST' | 'PUT' | 'DELETE' = 'GET'
    ) {

        let base = new URL(this.options.rest_url);

        try {


            for (let key of Object.keys(params)) {
                if (!base.searchParams.has(key)) {
                    base.searchParams.append(key, params[key]);
                }
            }

            base.pathname += endpoint;

            const response = await fetch(base, {
                method: type,
                headers: {
                    "Content-Type": "application/json",
                }
            });

            return this.throwIfError(response);
        } catch (error) {
            console.error(error);
            throw error;
        }

    }
    rest() {
        return this
    }
}

export default ApiService;
