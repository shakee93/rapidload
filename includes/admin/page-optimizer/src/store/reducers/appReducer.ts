// appReducer.ts
import { Dispatch } from 'redux';
import axios, { AxiosResponse } from 'axios';
import { ThunkAction, ThunkDispatch } from 'redux-thunk';
import { AnyAction } from 'redux';
import {useOptimizerContext} from "../../context/root";

// Define action types
const FETCH_DATA_SUCCESS = 'FETCH_DATA_SUCCESS';
const FETCH_DATA_FAILURE = 'FETCH_DATA_FAILURE';

// Define action interfaces
interface FetchDataSuccessAction {
    type: typeof FETCH_DATA_SUCCESS;
    payload: any; // Replace 'any' with the actual type of data you expect from the API
}

interface FetchDataFailureAction {
    type: typeof FETCH_DATA_FAILURE;
    error: string;
}

// Define the combined action type
export type AppAction = FetchDataSuccessAction | FetchDataFailureAction;

// Define the initial state for the reducer
export interface AppState {
    data: any | null; // Replace 'any' with the actual type of data you expect from the API
    error: string | null;
}

const initialState: AppState = {
    data: null,
    error: null,
};

// Define the reducer function
const appReducer = (state = initialState, action: AppAction): AppState => {
    switch (action.type) {
        case FETCH_DATA_SUCCESS:
            return {
                ...state,
                data: action.payload,
                error: null,
            };
        case FETCH_DATA_FAILURE:
            return {
                ...state,
                data: null,
                error: action.error,
            };
        default:
            return state;
    }
};

export default appReducer;

// Action creator to fetch data from the API
export const fetchData = (): ThunkAction<void, AppState, unknown, AnyAction> => {
    const {options} = useOptimizerContext();
    let url = 'http://localhost:5173/api'

    if (options.ajax_url) {
        url = options.ajax_url
    }
    
    console.log(url);
    
    return async (dispatch: ThunkDispatch<AppState, unknown, AppAction>) => {
        try {
            const response: AxiosResponse<any> = await axios.get(url);
            dispatch({ type: FETCH_DATA_SUCCESS, payload: response.data });
        } catch (error) {
            if (error instanceof Error) {
                dispatch({ type: FETCH_DATA_FAILURE, error: error.message });
            } else {
                dispatch({ type: FETCH_DATA_FAILURE, error: 'Unknown error occurred' });
            }
        }
    };
};