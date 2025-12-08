import Router from "express"
import {loginFunction, getUserInfo} from "../service/axiosService.js"

const authRouter = Router()

authRouter.post("/login", async (req,res)=>{
    const { login, password } = req.body;

    try {
        const token = await loginFunction(login, password);
        const userInfo = await getUserInfo(token);
        console.log(token);

        res.send({
            succes:"ok",
            token,
            user: userInfo
        });
    } catch (err) {
        res.send({
            succes:"Error while login"
        });
    }
})

authRouter.get("/me", async (req,res)=>{
    const token = req.get('DOLAPIKEY');

    try {
        const userInfo = await getUserInfo(token);
        res.send({
            status:"ok",
            result: userInfo
        });
    } catch (err) {
        res.send({
            status:"error"
        });
    }
})

export default authRouter
