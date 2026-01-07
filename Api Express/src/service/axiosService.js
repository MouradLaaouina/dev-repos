import axios from 'axios';
import 'dotenv/config'


//const BASE_URL = process.env.BASE_URL || "http://dolibarr"
const BASE_URL = process.env.BASE_URL || "https://ns327060.ip-5-135-138.eu/test/api/index.php"


export const loginFunction = async (login,password)=> {
    try {
        const res = await axios.post(
            BASE_URL+"/login", 
            {
                login,
                password
            }
        );
        return res.data.success.token; 
    } catch (err) {
        console.error(err);
        throw err;
    }
}

export const getWithToken = async (url,token) =>{
    const config = {
        headers: { DOLAPIKEY: `${token}` }
    };

    try {
        const res = await axios.get(
            `${BASE_URL}${url}`,
            config
        )
         return res.data; 
    } catch (err) {
        console.error(err);
        throw err;
    }
}   

export const postWithToken = async (url,body,token)=>{
       const config = {
        headers: { DOLAPIKEY: `${token}` }
    };

    try {
        const res = await axios.post(
            `${BASE_URL}${url}`,
            body,
            config
        )
        return res.data;
    } catch (err) {
        console.error(err);
        throw err;
    }
}

export const putWithToken = async (url,body,token)=>{
    const config = {
     headers: { DOLAPIKEY: `${token}` }
 };

 try {
     const res = await axios.put(
         `${BASE_URL}${url}`,
         body,
         config
     )
     return res.data;
 } catch (err) {
     console.error(err);
     throw err;
 }
}

export const deleteWithToken = async (url,token)=>{
    const config = {
     headers: { DOLAPIKEY: `${token}` }
 };

 try {
     const res = await axios.delete(
         `${BASE_URL}${url}`,
         config
     )
     return res.data;
 } catch (err) {
     console.error(err);
     throw err;
 }
}

export const getUserInfo = async (token) => {
    try {
        const res = await getWithToken('/users/info', token);
        return res;
    } catch (err) {
        console.error(err);
        throw err;
    }
}





