import Router from "express"
import { getWithToken } from "../service/axiosService.js"

const userRouter = Router()

userRouter.get("/", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    try{
        const response = await getWithToken("/users",token)
        res.send({
            status:"ok",
            result: response
        })
    }catch(error){
        res.status(500).send({
            status:"error",
            message: error.message
        })
    }
})

export default userRouter
