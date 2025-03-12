import { isDev, toBoolean } from "lib/utils";
import store from "../store";
import { toast } from "components/ui/use-toast";
import sampleData from "../lib/sample-pagespeed.json";
class ApiService {
    public baseURL: URL;
    private options: WordPressOptions;
    public aiBaseURL: URL;


    constructor(options?: WordPressOptions, query?: string, action?: string) {

        if (!options) {
            options = window.rapidload_optimizer
        }

        this.options = options

        this.aiBaseURL = new URL(options?.ai_root || 'https://ai.rapidload.io/api');

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

    private setSearchParams(params: Record<string, string>) {
        // Clear all existing params
        this.baseURL.search = '';

        // Add new params
        Object.entries(params).forEach(([key, value]) => {
            this.baseURL.searchParams.append(key, value);
        });
    }

    async fetchPageSpeed(url: string, activeReport: string, reload: boolean, abortController?: AbortController, noRapidLoad?: boolean): Promise<any> {
       
        try {
            let fresh = reload
            let data = null

            if (reload) {

                data = await this.analyzeViaAPI(url, activeReport, abortController, noRapidLoad);

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
            }

            // this.setSearchParams({
            //     action: 'fetch_page_speed',
            //     url,
            //     strategy: activeReport,
            //     new: reload as unknown as string,
            //     is_dev: isDev as unknown as string
            // });

            //console.log("this.baseURL: ", this.baseURL)
            const query = new URLSearchParams();

            // Clear existing search params before adding new ones
            this.baseURL.searchParams.delete('action');
            this.baseURL.searchParams.delete('url');
            this.baseURL.searchParams.delete('strategy');
            this.baseURL.searchParams.delete('new');
            this.baseURL.searchParams.delete('is_dev');

            this.baseURL.searchParams.append('action', 'fetch_page_speed')
            this.baseURL.searchParams.append('url', url)
            this.baseURL.searchParams.append('strategy', activeReport)
            this.baseURL.searchParams.append('new', reload as unknown as string)
            this.baseURL.searchParams.append('is_dev', isDev as unknown as string)
            // this.baseURL.searchParams.append('settingsMode', settingsMode || '')

            const response = await fetch(this.baseURL, {
                method: data ? "POST" : "GET",
                headers: {
                    "Content-Type": "application/json",
                },
                signal: abortController?.signal,
                ...(
                    data ? {
                        body: JSON.stringify({
                            page_speed: data
                        })
                    } : {}
                )
            });


            let responseData = await this.throwIfError(response, {
                fresh: reload
            });

            if (responseData?.reload) {
                return await this.fetchPageSpeed(url, activeReport, true);
            }

            return responseData

        } catch (error) {
            console.error(error);
            throw error;
        }
    }



    async fetchSettings(url: string, activeReport: string, reload: boolean): Promise<any> {

        try {

            let fresh = reload
            let data = null

            const query = new URLSearchParams();

            this.baseURL.searchParams.append('action', 'fetch_titan_settings')
            this.baseURL.searchParams.append('url', url)
            this.baseURL.searchParams.append('strategy', activeReport)
            this.baseURL.searchParams.append('new', reload as unknown as string)
            this.baseURL.searchParams.append('is_dev', isDev as unknown as string)
            // this.baseURL.searchParams.append('settingsMode', settingsMode || '')

            const response = await fetch(this.baseURL, {
                method: data ? "POST" : "GET",
                headers: {
                    "Content-Type": "application/json",
                }
            });


            let responseData = await this.throwIfError(response, {
                fresh: reload
            });

            if (responseData?.reload) {
                return await this.fetchPageSpeed(url, activeReport, true);
            }

            return responseData

        } catch (error) {
            //console.error(error);
            toast({
                description: 'Failed to fetch RapidLoad settings!',
                variant: 'destructive',
            })
            throw error;
        }
    }

    async analyzeViaAPI(url: string, strategy: string, abortController?: AbortController, noRapidLoad?: boolean) {

       // console.log('analyzeViaAPI', abortController);

        try {

            const state = store.getState()
            const data = state.app.report[state.app.activeReport]
            const settings = state.app.settings.performance[state.app.activeReport]
            const testModeStatus = state.app.testMode?.status ?? state.app.settings.general.test_mode ?? false;
            const previewUrl = testModeStatus || !noRapidLoad ? '?rapidload_preview' : '';

            const api_root = this.options?.api_root || 'https://api.rapidload.io/api/v1';
            const pageSpeedURL = new URL(`${api_root}/page-speed`);

            pageSpeedURL.searchParams.append('url', url + previewUrl)
            // pageSpeedURL.searchParams.append('strategy', state.app.activeReport)
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
                signal: abortController?.signal,
                body: JSON.stringify({
                    settings: settings.state?.
                        flatMap(t =>
                            t.inputs
                                .filter(({ value }) => value != null)
                                .map(({ key, value }) => ({ key, value, status: t.status })))
                        || []
                })
            });

            // TO TEST: remove return sampleData and comment above fetch
            return await pageSpeed.json()

        } catch (error) {
            console.error(error);
            throw error;
        }
    }

    async getAiPrediction(url: string, score: number, audits: any, metrics: any): Promise<any> {
        try {

            const ai_prediction_url = new URL(`${this.aiBaseURL}/score`);

            ai_prediction_url.searchParams.append('url', url);
            ai_prediction_url.searchParams.append('score', score.toString());

            const response = await fetch(ai_prediction_url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    audits,
                    metrics
                })
            });

            if (!response.ok) {
                throw new Error('AI prediction request failed');
            }

            const data = await response.json();
            return {
                success: true,
                data: data
            };

        } catch (error) {
            console.error('AI Prediction Error:', error);
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

    async updateDiagnosticResults(url: string, activeReport: string, data?: any): Promise<any> {
        try {
            this.baseURL.searchParams.append('action', 'rapidload_diagnose_data');
            this.baseURL.searchParams.append('url', url)
            this.baseURL.searchParams.append('strategy', activeReport)

            const response = await fetch(this.baseURL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                ...(data ? {
                    body: JSON.stringify({
                        data: data
                    })
                } : {})
            });
            return this.throwIfError(response);
        } catch (error) {
            console.error(error);
            throw error;
        }
    }

    async getTestMode(url: string, mode: string): Promise<any> {
        try {
            this.baseURL.searchParams.append('action', 'rapidload_switch_test_mode');
            this.baseURL.searchParams.append('url', url)
            this.baseURL.searchParams.append('test_mode', mode)

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

    async getSummary(action: string): Promise<any> {
        try {
            this.baseURL.searchParams.append('action', action);

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

    async getOptimizationData(startFrom: number, limit: number): Promise<any> {
        try {
            this.baseURL.searchParams.append('action', 'rapidload_titan_optimizations_data');
            this.baseURL.searchParams.append('start_from', startFrom)
            this.baseURL.searchParams.append('limit', limit)

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


    async searchData(action: string, searchFor: string, postType?: string): Promise<any> {
        try {
            this.baseURL.searchParams.append('action', action);
            this.baseURL.searchParams.append('s', searchFor)
            this.baseURL.searchParams.append('post_type', postType)

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

    async deleteOptimizedData(url: string): Promise<any> {
        try {
            this.baseURL.searchParams.append('action', 'rapidload_delete_titan_optimizations');
            this.baseURL.searchParams.append('url', url)

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

    async saveGeneralSettings(data: any): Promise<any> {
        try {
            const formData = new FormData();
            this.baseURL.searchParams.append('action', 'update_rapidload_settings');
            Object.keys(data).forEach(key => {
                if (Array.isArray(data[key])) {
                    data[key].forEach((item: any, index: number) => {
                        formData.append(`${key}[${index}]`, item);
                    });
                } else {
                    formData.append(key, data[key]);
                }
            });
            const response = await fetch(this.baseURL, {
                method: 'POST',
                body: formData,
            });
            return this.throwIfError(response);
        } catch (error) {
            console.error(error);
            throw error;
        }
    }

    // async updateLicense(data?: any): Promise<any> {
    //     try {
    //         this.baseURL.searchParams.append('action', data? 'uucss_connect': 'uucss_license');
    //
    //         const formData = new FormData();
    //         formData.append('license_key', data);
    //
    //         const response = await fetch(this.baseURL, {
    //             method: 'POST',
    //             body: formData,
    //         });
    //         return this.throwIfError(response);
    //     } catch (error) {
    //         console.error(error);
    //         throw error;
    //     }
    // }

    async updateLicense(data?: any): Promise<any> {
        try {
            this.baseURL.searchParams.append('action', data ? 'uucss_connect' : 'uucss_license');

            const formData = new FormData();
            formData.append('license_key', data);

            const response = await fetch(this.baseURL, {
                method: 'POST',
                body: formData,
            });

            const responseData = await response.json();
            return responseData;
        } catch (error) {
            console.error('Error in updateLicense:', error);
            return { success: false, data: "An unknown error occurred" };
        }
    }

    async fetchPosts(): Promise<any> {
        try {
            this.baseURL.searchParams.append('action', 'rapidload_fetch_post_types_with_links');

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

    async homePagePerformance(): Promise<any> {
        try {
            this.baseURL.searchParams.append('action', 'rapidload_titan_home_page_performance');

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

    async getLicense(): Promise<any> {
        try {
            this.baseURL.searchParams.append('action', 'uucss_license');

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

    async getActivePlugins(): Promise<any> {
        try {
            this.baseURL.searchParams.append('action', 'rapidload_get_active_plugins');

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

    rest() {
        return this
    }
}

export default ApiService;
