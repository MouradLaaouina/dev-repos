import Router from "express";
import jwt from "jsonwebtoken";
import { loginFunction, getUserInfo, signup } from "../service/axiosService.js";

const authRouter = Router();

const JWT_SECRET = process.env.JWT_SECRET;

if (!JWT_SECRET) {
    throw new Error('JWT_SECRET environment variable is not set!');
}

authRouter.post("/login", async (req, res) => {
    const { login, password } = req.body;

    try {
        const dolibarrToken = await loginFunction(login, password);
        const userInfo = await getUserInfo(dolibarrToken);

        const token = jwt.sign({ id: userInfo.id, login: userInfo.login }, JWT_SECRET, {
            expiresIn: "1h",
        });

        res.send({
            succes: "ok",
            token,
            user: userInfo,
        });
    } catch (err) {
        res.status(401).send({
            succes: "Error while login",
        });
    }
});

authRouter.post("/signup", async (req, res) => {
    const { email, password, firstname, lastname } = req.body;

    try {
        const newUser = await signup({
            login: email,
            password,
            firstname,
            lastname,
            email,
        });

        const dolibarrToken = await loginFunction(email, password);
        const userInfo = await getUserInfo(dolibarrToken);

        const token = jwt.sign({ id: userInfo.id, login: userInfo.login }, JWT_SECRET, {
            expiresIn: "1h",
        });

        res.send({
            succes: "ok",
            token,
            user: userInfo,
        });
    } catch (err) {
        res.status(400).send({
            succes: "Error while signup",
        });
    }
});

authRouter.post("/logout", (req, res) => {
    res.send({ succes: "ok" });
});


authRouter.get("/me", async (req,res)=>{
    const authHeader = req.headers.authorization;

    if (!authHeader) {
        return res.status(401).send({ status: "error", message: "Missing Authorization header" });
    }

    const token = authHeader.split(' ')[1];

    try {
        const decoded = jwt.verify(token, JWT_SECRET);
        // We need an admin token to get user info by id
        const userInfo = await getUserInfo(process.env.DOLAPIKEY, decoded.id);
        res.send({
            status:"ok",
            result: userInfo
        });
    } catch (err) {
        res.status(401).send({
            status:"error"
        });
    }
})

export default authRouter;
